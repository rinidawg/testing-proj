<?php

namespace App\Http\Controllers\Subcon;

use App\Http\Controllers\Controller;
use App\Models\Subcontractor;
use App\Services\Subcon\SubconPayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubcontractorController extends Controller
{
    public function __construct(protected SubconPayoutService $payouts) {}

    public function index(): JsonResponse
    {
        $subs = Subcontractor::orderBy('name')->get()->map(function ($s) {
            return array_merge($s->toArray(), ['true_cost' => $this->payouts->trueCost($s->id)]);
        });

        return response()->json(['subcontractors' => $subs]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $sub = Subcontractor::create($data + ['is_active' => true]);

        return response()->json(['subcontractor' => $sub], 201);
    }
}
