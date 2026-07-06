<?php

namespace App\Http\Controllers\Subcon;

use App\Http\Controllers\Controller;
use App\Models\Subcontractor;
use App\Services\Subcon\SubconPayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubconPayoutController extends Controller
{
    public function __construct(protected SubconPayoutService $payouts) {}

    /** GET /subcon/payouts/week?date=YYYY-MM-DD  (date can be any day in the week) */
    public function week(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());
        $week = $this->payouts->computeWeek($date);

        // attach names
        $names = Subcontractor::pluck('name', 'id');
        foreach ($week['subs'] as &$row) {
            $row['name'] = $names[$row['subcontractor_id']] ?? ('#' . $row['subcontractor_id']);
        }
        unset($row);

        return response()->json($week);
    }

    /** POST /subcon/payouts/pay  { subcontractor_id, week_start, paid_at? } */
    public function pay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subcontractor_id' => ['required', 'integer', 'exists:subcontractors,id'],
            'week_start'       => ['required', 'date'],
            'paid_at'          => ['nullable', 'date'],
        ]);

        $payout = $this->payouts->markPaid(
            $data['subcontractor_id'],
            $data['week_start'],
            $data['paid_at'] ?? null,
            $request->user()->id,
        );

        return response()->json(['payout' => $payout]);
    }

    /** GET /subcon/payouts/export?date=YYYY-MM-DD */
    public function export(Request $request): StreamedResponse
    {
        $week = $this->payouts->computeWeek($request->query('date', now()->toDateString()));
        $names = Subcontractor::pluck('name', 'id');

        return response()->streamDownload(function () use ($week, $names) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ASH AI - Subcon Weekly Payout']);
            fputcsv($out, ['Week starting', $week['week_start']]);
            fputcsv($out, []);
            fputcsv($out, ['Subcon', 'PO', 'Style', 'Kind', 'Pcs', 'Rate', 'Amount']);
            foreach ($week['subs'] as $row) {
                $name = $names[$row['subcontractor_id']] ?? ('#' . $row['subcontractor_id']);
                foreach ($row['lines'] as $l) {
                    fputcsv($out, [$name, $l['po_code'], $l['style'], $l['kind'], $l['pcs'],
                        number_format($l['rate'], 2, '.', ''), number_format($l['amount'], 2, '.', '')]);
                }
                fputcsv($out, [$name . ' TOTAL', '', '', '', '', '', number_format($row['total'], 2, '.', '')]);
            }
            fputcsv($out, []);
            fputcsv($out, ['GRAND TOTAL', '', '', '', '', '', number_format($week['grand'], 2, '.', '')]);
            fclose($out);
        }, 'subcon-payout-' . $week['week_start'] . '.csv', ['Content-Type' => 'text/csv']);
    }
}
