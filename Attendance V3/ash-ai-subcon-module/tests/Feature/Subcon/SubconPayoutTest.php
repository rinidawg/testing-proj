<?php

use App\Models\SubconDelivery;
use App\Models\SubconPo;
use App\Models\Subcontractor;
use App\Services\Subcon\SubconPayoutService;
use App\Support\SubconConstants as SC;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Service-level tests — no permission middleware, so no Spatie seeding needed.
 */

function makePo(int $subId, float $rate): SubconPo
{
    return SubconPo::create([
        'code' => 'SUB-TEST-' . uniqid(), 'subcontractor_id' => $subId, 'style' => 'Tee',
        'qty' => 500, 'rate' => $rate, 'po_date' => '2026-06-20', 'status' => SC::PO_OPEN,
    ]);
}

it('pays accepted pieces in the delivery week', function () {
    $sub = Subcontractor::create(['name' => 'Nena']);
    $po = makePo($sub->id, 22);
    SubconDelivery::create([
        'subcon_po_id' => $po->id, 'delivery_date' => '2026-07-01', // Wed of week starting 2026-06-29
        'delivered_qty' => 150, 'accepted_qty' => 150, 'reject_qty' => 0, 'repairs' => [], 'scraps' => [],
    ]);

    $week = app(SubconPayoutService::class)->computeWeek('2026-07-03');

    expect($week['week_start'])->toBe('2026-06-29');
    expect($week['grand'])->toBe(3300.0); // 150 * 22
});

it('does not pay rejects until repaired, and attributes repairs to the repair week', function () {
    $sub = Subcontractor::create(['name' => 'JM']);
    $po = makePo($sub->id, 20);
    SubconDelivery::create([
        'subcon_po_id' => $po->id, 'delivery_date' => '2026-06-24', // week of 2026-06-22
        'delivered_qty' => 100, 'accepted_qty' => 90, 'reject_qty' => 10,
        'repairs' => [['qty' => 6, 'date' => '2026-07-01']],           // repaired next week
        'scraps'  => [['qty' => 2, 'date' => '2026-07-01']],           // scrapped, never paid
    ]);
    $svc = app(SubconPayoutService::class);

    // delivery week: only the 90 accepted are payable (rejects excluded)
    expect($svc->computeWeek('2026-06-24')['grand'])->toBe(1800.0); // 90 * 20

    // repair week: only the 6 repaired are payable (the 2 scrapped never are)
    expect($svc->computeWeek('2026-07-01')['grand'])->toBe(120.0);  // 6 * 20
});

it('records a payout once per week and is idempotent on repost', function () {
    $sub = Subcontractor::create(['name' => 'Boy']);
    $po = makePo($sub->id, 24);
    SubconDelivery::create([
        'subcon_po_id' => $po->id, 'delivery_date' => '2026-07-02',
        'delivered_qty' => 100, 'accepted_qty' => 100, 'reject_qty' => 0, 'repairs' => [], 'scraps' => [],
    ]);
    $svc = app(SubconPayoutService::class);

    $first  = $svc->markPaid($sub->id, '2026-06-29', '2026-07-05');
    $second = $svc->markPaid($sub->id, '2026-06-29', '2026-07-05'); // repost

    expect($second->id)->toBe($first->id);
    expect(\App\Models\SubconPayout::count())->toBe(1);
    expect((float) $second->amount)->toBe(2400.0);
});

it('computes true cost per piece including logistics and extra trips', function () {
    $sub = Subcontractor::create(['name' => 'Nena']);
    $po = makePo($sub->id, 22);
    SubconDelivery::create([
        'subcon_po_id' => $po->id, 'delivery_date' => '2026-07-01',
        'delivered_qty' => 100, 'accepted_qty' => 100, 'reject_qty' => 0, 'repairs' => [], 'scraps' => [],
    ]);
    \App\Models\SubconTrip::create(['subcontractor_id' => $sub->id, 'kind' => SC::TRIP_HATID, 'amount' => 245, 'trip_date' => '2026-06-20']);
    \App\Models\SubconTrip::create(['subcontractor_id' => $sub->id, 'kind' => SC::TRIP_EXTRA_NAIWAN, 'amount' => 175, 'trip_date' => '2026-07-02']);

    $tc = app(SubconPayoutService::class)->trueCost($sub->id);

    expect($tc['pcs'])->toBe(100);
    expect($tc['sewing'])->toBe(2200.0);
    expect($tc['logistics'])->toBe(420.0);
    expect($tc['extra'])->toBe(175.0);
    expect($tc['per_piece'])->toBe(26.2); // (2200 + 420) / 100
});
