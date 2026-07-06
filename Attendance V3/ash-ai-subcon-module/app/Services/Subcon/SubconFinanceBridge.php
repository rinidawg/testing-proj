<?php

namespace App\Services\Subcon;

use App\Support\SubconConstants as SC;
use Illuminate\Support\Carbon;

/**
 * Thin, guarded bridge to the Finance module. If the finance module is
 * installed (FinanceTransaction model present), subcon money movements are
 * posted into the books using the SAME idempotent (source, source_ref)
 * convention. If it isn't installed yet, these calls are no-ops, so the
 * subcon module works standalone.
 *
 * Idempotency:
 *   - weekly payout : source=subcon, source_ref=SUBCON-{subId}-{YYYY-MM-DD week}
 *   - logistics trip: source=sublog, source_ref=SUBLOG-{tripId}
 * Reposting the same event updates the existing row instead of duplicating.
 */
class SubconFinanceBridge
{
    protected static function financeAvailable(): bool
    {
        return class_exists(\App\Models\FinanceTransaction::class)
            && class_exists(\App\Models\FinanceCategory::class);
    }

    protected static function categoryId(string $name): ?int
    {
        if (! class_exists(\App\Models\FinanceCategory::class)) {
            return null;
        }

        $cat = \App\Models\FinanceCategory::firstOrCreate(
            ['name' => $name, 'type' => 'out'],
            ['is_system' => true, 'is_active' => true]
        );

        return $cat->id;
    }

    public static function postPayout(int $subId, string $weekStart, float $amount, Carbon $paidAt, string $note): void
    {
        if (! self::financeAvailable()) {
            return;
        }

        \App\Models\FinanceTransaction::updateOrCreate(
            ['source' => SC::FIN_SRC_SUBCON, 'source_ref' => 'SUBCON-' . $subId . '-' . $weekStart],
            [
                'type'        => 'out',
                'txn_date'    => $paidAt->toDateString(),
                'category_id' => self::categoryId('Subcon sewing'),
                'brand'       => 'General',
                'amount'      => round($amount, 2),
                'note'        => $note,
            ]
        );
    }

    public static function postTrip(int $tripId, float $amount, Carbon $tripDate, string $note): void
    {
        if (! self::financeAvailable()) {
            return;
        }

        \App\Models\FinanceTransaction::updateOrCreate(
            ['source' => SC::FIN_SRC_SUBLOG, 'source_ref' => 'SUBLOG-' . $tripId],
            [
                'type'        => 'out',
                'txn_date'    => $tripDate->toDateString(),
                'category_id' => self::categoryId('Subcon logistics'),
                'brand'       => 'General',
                'amount'      => round($amount, 2),
                'note'        => $note,
            ]
        );
    }

    public static function removeTrip(int $tripId): void
    {
        if (! self::financeAvailable()) {
            return;
        }

        \App\Models\FinanceTransaction::where('source', SC::FIN_SRC_SUBLOG)
            ->where('source_ref', 'SUBLOG-' . $tripId)
            ->delete();
    }
}
