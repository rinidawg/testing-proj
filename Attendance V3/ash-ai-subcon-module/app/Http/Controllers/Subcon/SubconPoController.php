<?php

namespace App\Http\Controllers\Subcon;

use App\Http\Controllers\Controller;
use App\Models\SubconPo;
use App\Support\SubconConstants as SC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubconPoController extends Controller
{
    public function index(): JsonResponse
    {
        $pos = SubconPo::with('subcontractor:id,name')->orderByDesc('po_date')->orderByDesc('id')->get()
            ->map(function (SubconPo $p) {
                $delivered = $p->deliveredQty();
                return array_merge($p->toArray(), [
                    'delivered_qty' => $delivered,
                    'remaining_qty' => max(0, (int) $p->qty - $delivered),
                    'is_overdue'    => $p->status === SC::PO_OPEN && $p->due_date
                                        && now()->toDateString() > $p->due_date->toDateString(),
                ]);
            });

        return response()->json(['pos' => $pos]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subcontractor_id' => ['required', 'integer', 'exists:subcontractors,id'],
            'style'            => ['required', 'string', 'max:255'],
            'qty'              => ['required', 'integer', 'min:1'],
            'rate'             => ['required', 'numeric', 'gt:0'],
            'po_date'          => ['required', 'date'],
            'due_date'         => ['nullable', 'date'],
        ]);

        $code = 'SUB-' . now()->format('Y') . '-' . str_pad((string) (SubconPo::max('id') + 1), 3, '0', STR_PAD_LEFT);

        $po = SubconPo::create($data + [
            'code'       => $code,
            'status'     => SC::PO_OPEN,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['po' => $po], 201);
    }

    public function show(int $id): JsonResponse
    {
        $po = SubconPo::with(['subcontractor:id,name', 'deliveries'])->findOrFail($id);

        return response()->json(['po' => $po]);
    }
}
