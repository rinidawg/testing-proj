<?php

namespace App\Http\Controllers\Subcon;

use App\Http\Controllers\Controller;
use App\Models\SubconAttachment;
use App\Models\SubconDelivery;
use App\Models\SubconPo;
use App\Support\SubconConstants as SC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubconDeliveryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = SubconDelivery::with('po:id,code,style,subcontractor_id,rate')
            ->when($request->query('po_id'), fn ($q, $id) => $q->where('subcon_po_id', $id))
            ->orderByDesc('delivery_date')->orderByDesc('id')->get()
            ->map(function (SubconDelivery $d) {
                return array_merge($d->toArray(), [
                    'repaired_qty'    => $d->repairedQty(),
                    'scrapped_qty'    => $d->scrappedQty(),
                    'pending_rejects' => $d->pendingRejects(),
                ]);
            });

        return response()->json(['deliveries' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subcon_po_id'  => ['required', 'integer', 'exists:subcon_pos,id'],
            'delivery_date' => ['required', 'date'],
            'delivered_qty' => ['required', 'integer', 'min:1'],
            'accepted_qty'  => ['required', 'integer', 'min:0'],
        ]);

        $po = SubconPo::findOrFail($data['subcon_po_id']);
        $remaining = max(0, (int) $po->qty - $po->deliveredQty());

        $delivered = min($data['delivered_qty'], $remaining);
        if ($delivered < 1) {
            abort(422, 'This PO is already fully delivered.');
        }
        $accepted = min($data['accepted_qty'], $delivered);

        $delivery = SubconDelivery::create([
            'subcon_po_id'  => $po->id,
            'delivery_date' => $data['delivery_date'],
            'delivered_qty' => $delivered,
            'accepted_qty'  => $accepted,
            'reject_qty'    => $delivered - $accepted,
            'repairs'       => [],
            'scraps'        => [],
            'received_by'   => $request->user()->id,
        ]);

        $this->syncPoStatus($po);

        return response()->json(['delivery' => $delivery], 201);
    }

    public function repair(Request $request, int $id): JsonResponse
    {
        return $this->appendResolution($request, $id, 'repairs');
    }

    public function scrap(Request $request, int $id): JsonResponse
    {
        return $this->appendResolution($request, $id, 'scraps');
    }

    public function destroy(int $id): JsonResponse
    {
        $delivery = SubconDelivery::findOrFail($id);
        $po = $delivery->po;

        SubconAttachment::where('owner_type', SC::OWNER_DELIVERY)->where('owner_id', $delivery->id)->delete();
        $delivery->delete();

        if ($po) {
            $this->syncPoStatus($po);
        }

        return response()->json(['deleted' => true]);
    }

    protected function appendResolution(Request $request, int $id, string $bucket): JsonResponse
    {
        $data = $request->validate([
            'qty'    => ['required', 'integer', 'min:1'],
            'date'   => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $delivery = SubconDelivery::findOrFail($id);
        $pending = $delivery->pendingRejects();
        if ($data['qty'] > $pending) {
            abort(422, "Only {$pending} rejected piece(s) remain to resolve.");
        }

        $list = $delivery->{$bucket} ?? [];
        $entry = ['qty' => (int) $data['qty'], 'date' => $data['date']];
        if ($bucket === 'scraps' && ! empty($data['reason'])) {
            $entry['reason'] = $data['reason'];
        }
        $list[] = $entry;
        $delivery->{$bucket} = $list;
        $delivery->save();

        return response()->json(['delivery' => $delivery->fresh()]);
    }

    protected function syncPoStatus(SubconPo $po): void
    {
        $status = $po->deliveredQty() >= (int) $po->qty ? SC::PO_COMPLETE : SC::PO_OPEN;
        if ($po->status !== $status) {
            $po->update(['status' => $status]);
        }
    }
}
