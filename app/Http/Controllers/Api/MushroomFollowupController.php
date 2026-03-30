<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MushroomFollowup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MushroomFollowupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MushroomFollowup::with([
            'allocation.household',
            'allocation.quota',
        ]);

        if ($allocationId = $request->input('allocation_id')) {
            $query->where('allocation_id', $allocationId);
        }
        if ($channel = $request->input('sale_channel')) {
            $query->where('sale_channel', $channel);
        }

        $followups = $query->orderBy('followup_date', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($followups);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'allocation_id'     => ['required', 'exists:mushroom_allocations,id'],
            'followup_round'    => ['required', 'integer', 'min:1'],
            'followup_date'     => ['nullable', 'date'],
            'harvest_kg'        => ['nullable', 'numeric', 'min:0'],
            'sold_kg'           => ['nullable', 'numeric', 'min:0'],
            'price_per_kg'      => ['nullable', 'numeric', 'min:0'],
            'revenue'           => ['nullable', 'numeric', 'min:0'],
            'sale_channel'      => ['nullable', 'in:direct,online,enterprise,market'],
            'sale_place'        => ['nullable', 'string', 'max:255'],
            'enterprise_member' => ['boolean'],
            'enterprise_name'   => ['nullable', 'string', 'max:255'],
            'note'              => ['nullable', 'string'],
        ]);

        // คำนวณรายได้อัตโนมัติ
        if (empty($validated['revenue']) && !empty($validated['sold_kg']) && !empty($validated['price_per_kg'])) {
            $validated['revenue'] = (float) $validated['sold_kg'] * (float) $validated['price_per_kg'];
        }

        $followup = MushroomFollowup::create($validated);
        $followup->load(['allocation.household', 'allocation.quota']);

        return response()->json($followup, 201);
    }

    public function show(MushroomFollowup $mushroomFollowup): JsonResponse
    {
        $mushroomFollowup->load(['allocation.household', 'allocation.quota']);

        return response()->json($mushroomFollowup);
    }

    public function update(Request $request, MushroomFollowup $mushroomFollowup): JsonResponse
    {
        $validated = $request->validate([
            'followup_date'     => ['nullable', 'date'],
            'harvest_kg'        => ['nullable', 'numeric', 'min:0'],
            'sold_kg'           => ['nullable', 'numeric', 'min:0'],
            'price_per_kg'      => ['nullable', 'numeric', 'min:0'],
            'revenue'           => ['nullable', 'numeric', 'min:0'],
            'sale_channel'      => ['nullable', 'in:direct,online,enterprise,market'],
            'sale_place'        => ['nullable', 'string', 'max:255'],
            'enterprise_member' => ['boolean'],
            'enterprise_name'   => ['nullable', 'string', 'max:255'],
            'note'              => ['nullable', 'string'],
        ]);

        // คำนวณรายได้อัตโนมัติถ้า revenue ไม่ได้ส่งมา
        if (!array_key_exists('revenue', $validated) || is_null($validated['revenue'])) {
            $soldKg = $validated['sold_kg'] ?? $mushroomFollowup->sold_kg;
            $pricePerKg = $validated['price_per_kg'] ?? $mushroomFollowup->price_per_kg;
            if ($soldKg && $pricePerKg) {
                $validated['revenue'] = (float) $soldKg * (float) $pricePerKg;
            }
        }

        $mushroomFollowup->update($validated);

        return response()->json($mushroomFollowup);
    }

    public function destroy(MushroomFollowup $mushroomFollowup): JsonResponse
    {
        $mushroomFollowup->delete();

        return response()->json(['message' => 'ลบข้อมูลติดตามผลสำเร็จ']);
    }
}
