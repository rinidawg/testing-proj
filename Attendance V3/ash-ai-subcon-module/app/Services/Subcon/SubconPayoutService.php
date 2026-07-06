<?php

namespace App\Services\Subcon;

use App\Models\SubconDelivery;
use App\Models\SubconPayout;
use App\Models\SubconTrip;
use App\Support\SubconConstants as SC;
use Illuminate\Support\Carbon;

/**
 * Weekly (Mon-Sun) subcontractor payout.
 *
 * Payable = accepted pieces delivered in the week + pieces repaired in the
 * week, each valued at the piece rate of their PO. Rejected pieces are NOT
 * payable until repaired; scrapped pieces are never paid.
 *
 * markPaid() records the payout once per (subcontractor, week) and posts the
 * expense into Finance idempotently.
 */
class SubconPayoutService
{
    /** Monday (business week start) for any date. */
    public function weekStart(string|Carbon $date): string
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        return $d->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /**
     * Compute the payout breakdown for one week.
     *
     * @return array{week_start:string, subs:array<int,array>, grand:float}
     */
    public function computeWeek(string $weekStart): array
    {
        $monday = Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY);
        $ws = $monday->toDateString();
        $we = $monday->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $subs = [];

        $deliveries = SubconDelivery::with('po.subcontractor')->get();

        foreach ($deliveries as $d) {
            $po = $d->po;
            if (! $po) {
                continue;
            }
            $subId = $po->subcontractor_id;
            $rate  = (float) $po->rate;

            // accepted pieces, attributed to the delivery week
            if ($d->accepted_qty > 0 && $this->weekStart($d->delivery_date) === $ws) {
                $subs[$subId]['lines'][] = [
                    'po_code' => $po->code, 'style' => $po->style, 'kind' => 'accepted',
                    'pcs' => (int) $d->accepted_qty, 'rate' => $rate,
                    'amount' => round($d->accepted_qty * $rate, 2),
                ];
            }

            // repaired pieces, attributed to the repair week
            foreach (($d->repairs ?? []) as $r) {
                $qty = (int) ($r['qty'] ?? 0);
                $rdate = $r['date'] ?? null;
                if ($qty > 0 && $rdate && $this->weekStart($rdate) === $ws) {
                    $subs[$subId]['lines'][] = [
                        'po_code' => $po->code, 'style' => $po->style, 'kind' => 'repaired',
                        'pcs' => $qty, 'rate' => $rate, 'amount' => round($qty * $rate, 2),
                    ];
                }
            }
        }

        $grand = 0.0;
        foreach ($subs as $subId => &$row) {
            $row['subcontractor_id'] = $subId;
            $row['total'] = round(array_sum(array_column($row['lines'], 'amount')), 2);
            $payout = SubconPayout::where('subcontractor_id', $subId)->whereDate('week_start', $ws)->first();
            $row['paid']        = (bool) $payout;
            $row['paid_amount'] = $payout ? (float) $payout->amount : null;
            $row['source_ref']  = 'SUBCON-' . $subId . '-' . $ws;
            $grand += $row['total'];
        }
        unset($row);

        return [
            'week_start' => $ws,
            'week_end'   => $we,
            'subs'       => array_values($subs),
            'grand'      => round($grand, 2),
        ];
    }

    /**
     * Record (or refresh) a payout for one subcontractor for one week and
     * post it to Finance. Safe to call again — updates, never duplicates.
     */
    public function markPaid(int $subId, string $weekStart, ?string $paidAt = null, ?int $paidBy = null): SubconPayout
    {
        $week = $this->computeWeek($weekStart);
        $ws = $week['week_start'];

        $amount = 0.0;
        foreach ($week['subs'] as $row) {
            if ((int) $row['subcontractor_id'] === $subId) {
                $amount = (float) $row['total'];
                break;
            }
        }

        $paid = $paidAt ? Carbon::parse($paidAt) : now();

        $payout = SubconPayout::updateOrCreate(
            ['subcontractor_id' => $subId, 'week_start' => $ws],
            ['amount' => $amount, 'status' => 'paid', 'paid_at' => $paid->toDateString(), 'paid_by' => $paidBy]
        );

        SubconFinanceBridge::postPayout(
            $subId, $ws, $amount, $paid,
            'Subcon sewing payout · week of ' . $ws
        );

        return $payout;
    }

    /**
     * True cost per accepted piece for a subcontractor:
     * (sewing earned + all logistics) / accepted pieces.
     *
     * @return array{pcs:int, sewing:float, logistics:float, extra:float, total:float, per_piece:?float}
     */
    public function trueCost(int $subId): array
    {
        $pcs = 0; $sewing = 0.0;
        $deliveries = SubconDelivery::whereHas('po', fn ($q) => $q->where('subcontractor_id', $subId))
            ->with('po')->get();

        foreach ($deliveries as $d) {
            $rate = (float) $d->po->rate;
            $earnedPcs = (int) $d->accepted_qty + $d->repairedQty();
            $pcs += $earnedPcs;
            $sewing += $earnedPcs * $rate;
        }

        $reg = 0.0; $extra = 0.0;
        foreach (SubconTrip::where('subcontractor_id', $subId)->get() as $t) {
            SC::isExtraTrip($t->kind) ? $extra += (float) $t->amount : $reg += (float) $t->amount;
        }

        $logistics = $reg + $extra;
        $total = $sewing + $logistics;

        return [
            'pcs' => $pcs,
            'sewing' => round($sewing, 2),
            'logistics' => round($logistics, 2),
            'extra' => round($extra, 2),
            'total' => round($total, 2),
            'per_piece' => $pcs > 0 ? round($total / $pcs, 2) : null,
        ];
    }
}
