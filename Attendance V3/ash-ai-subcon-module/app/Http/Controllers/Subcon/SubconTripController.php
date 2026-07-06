<?php

namespace App\Http\Controllers\Subcon;

use App\Http\Controllers\Controller;
use App\Models\SubconAttachment;
use App\Models\SubconPo;
use App\Models\SubconTrip;
use App\Services\Subcon\SubconFinanceBridge;
use App\Support\SubconConstants as SC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubconTripController extends Controller
{
    public function index(): JsonResponse
    {
        $trips = SubconTrip::with(['subcontractor:id,name', 'po:id,code'])
            ->orderByDesc('trip_date')->orderByDesc('id')->get();

        return response()->json(['trips' => $trips]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subcontractor_id' => ['required', 'integer', 'exists:subcontractors,id'],
            'subcon_po_id'     => ['nullable', 'integer', 'exists:subcon_pos,id'],
            'kind'             => ['required', 'in:' . implode(',', SC::TRIP_KINDS)],
            'amount'           => ['required', 'numeric', 'gt:0'],
            'trip_date'        => ['required', 'date'],
            'note'             => ['nullable', 'string', 'max:255'],
        ]);

        // Guard: never link a trip to a PO that belongs to another subcontractor.
        if (! empty($data['subcon_po_id'])) {
            $po = SubconPo::find($data['subcon_po_id']);
            if (! $po || $po->subcontractor_id !== (int) $data['subcontractor_id']) {
                $data['subcon_po_id'] = null;
            }
        }

        $trip = SubconTrip::create($data + ['created_by' => $request->user()->id]);

        SubconFinanceBridge::postTrip(
            $trip->id, (float) $trip->amount, $trip->trip_date,
            'Subcon logistics · ' . $trip->kind . ($trip->note ? ' · ' . $trip->note : '')
        );

        return response()->json(['trip' => $trip], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $trip = SubconTrip::findOrFail($id);

        SubconAttachment::where('owner_type', SC::OWNER_TRIP)->where('owner_id', $trip->id)->delete();
        SubconFinanceBridge::removeTrip($trip->id);
        $trip->delete();

        return response()->json(['deleted' => true]);
    }
}
