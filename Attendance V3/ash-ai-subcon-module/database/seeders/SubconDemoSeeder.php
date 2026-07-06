<?php

namespace Database\Seeders;

use App\Models\SubconDelivery;
use App\Models\SubconPo;
use App\Models\SubconTrip;
use App\Models\Subcontractor;
use App\Support\SubconConstants as SC;
use Illuminate\Database\Seeder;

/**
 * Optional sample data mirroring the tested HTML demo, so the module can be
 * exercised immediately. Safe to skip in production.
 */
class SubconDemoSeeder extends Seeder
{
    public function run(): void
    {
        $nena = Subcontractor::create(['name' => 'Aling Nena Sewing (Payatas)', 'is_active' => true]);
        $jm   = Subcontractor::create(['name' => 'JM Garments Subcon (Novaliches)', 'is_active' => true]);
        $boy  = Subcontractor::create(['name' => 'Tatay Boy Team (Fairview)', 'is_active' => true]);

        $po14 = SubconPo::create(['code' => 'SUB-2026-014', 'subcontractor_id' => $nena->id, 'style' => 'REEFER Boxy Tee — HA design', 'qty' => 300, 'rate' => 22, 'po_date' => '2026-06-20', 'due_date' => '2026-07-04', 'status' => SC::PO_OPEN]);
        $po15 = SubconPo::create(['code' => 'SUB-2026-015', 'subcontractor_id' => $jm->id, 'style' => 'Sorbetes Classic Tee — school org', 'qty' => 500, 'rate' => 18, 'po_date' => '2026-06-22', 'due_date' => '2026-07-10', 'status' => SC::PO_OPEN]);
        $po16 = SubconPo::create(['code' => 'SUB-2026-016', 'subcontractor_id' => $boy->id, 'style' => 'REEFER Classic Tee — WD', 'qty' => 250, 'rate' => 24, 'po_date' => '2026-06-25', 'due_date' => '2026-06-30', 'status' => SC::PO_OPEN]);

        SubconDelivery::create(['subcon_po_id' => $po14->id, 'delivery_date' => '2026-06-24', 'delivered_qty' => 150, 'accepted_qty' => 142, 'reject_qty' => 8, 'repairs' => [['qty' => 8, 'date' => '2026-06-30']], 'scraps' => []]);
        SubconDelivery::create(['subcon_po_id' => $po14->id, 'delivery_date' => '2026-07-01', 'delivered_qty' => 150, 'accepted_qty' => 150, 'reject_qty' => 0, 'repairs' => [], 'scraps' => []]);
        SubconDelivery::create(['subcon_po_id' => $po15->id, 'delivery_date' => '2026-06-26', 'delivered_qty' => 200, 'accepted_qty' => 195, 'reject_qty' => 5, 'repairs' => [['qty' => 3, 'date' => '2026-07-02']], 'scraps' => []]);
        SubconDelivery::create(['subcon_po_id' => $po16->id, 'delivery_date' => '2026-07-02', 'delivered_qty' => 120, 'accepted_qty' => 112, 'reject_qty' => 8, 'repairs' => [], 'scraps' => []]);

        SubconTrip::create(['subcontractor_id' => $nena->id, 'subcon_po_id' => $po14->id, 'kind' => SC::TRIP_HATID, 'amount' => 245, 'trip_date' => '2026-06-20', 'note' => 'Lalamove motor — 300 cut sets']);
        SubconTrip::create(['subcontractor_id' => $nena->id, 'subcon_po_id' => $po14->id, 'kind' => SC::TRIP_KUHA, 'amount' => 245, 'trip_date' => '2026-06-24', 'note' => '']);
        SubconTrip::create(['subcontractor_id' => $jm->id, 'subcon_po_id' => $po15->id, 'kind' => SC::TRIP_EXTRA_KULANG, 'amount' => 150, 'trip_date' => '2026-06-27', 'note' => 'kulang 5 pcs, binalikan']);
        SubconTrip::create(['subcontractor_id' => $boy->id, 'subcon_po_id' => $po16->id, 'kind' => SC::TRIP_EXTRA_NAIWAN, 'amount' => 175, 'trip_date' => '2026-07-02', 'note' => 'naiwan 1 bundle']);
    }
}
