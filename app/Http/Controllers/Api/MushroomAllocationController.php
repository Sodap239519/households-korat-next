<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MushroomAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MushroomAllocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MushroomAllocation::with(['quota', 'household']);

        if ($quotaId = $request->input('quota_id')) {
            $query->where('quota_id', $quotaId);
        }
        if ($householdId = $request->input('household_id')) {
            $query->where('household_id', $householdId);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $allocations = $query->orderBy('allocated_date', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($allocations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quota_id'       => ['required', 'exists:mushroom_quota_districts,id'],
            'household_id'   => ['required', 'exists:households,id'],
            'bags'           => ['required', 'integer', 'min:1'],
            'allocated_date' => ['nullable', 'date'],
            'status'         => ['nullable', 'in:pending,active,completed'],
            'note'           => ['nullable', 'string'],
        ]);

        // ตรวจสอบโควต้าคงเหลือ (trigger จะจัดการด้วย แต่ตรวจก่อนเพื่อ error message ที่ดี)
        $quota = \App\Models\MushroomQuotaDistrict::lockForUpdate()->findOrFail($validated['quota_id']);
        $allocated = $quota->allocations()->sum('bags');
        $remaining = $quota->quota_bags - $allocated;

        if ($validated['bags'] > $remaining) {
            return response()->json([
                'message' => "โควต้าไม่เพียงพอ (คงเหลือ: {$remaining} ถุง)",
            ], 422);
        }

        $allocation = MushroomAllocation::create($validated);
        $allocation->load(['quota', 'household']);

        return response()->json($allocation, 201);
    }

    public function show(MushroomAllocation $mushroomAllocation): JsonResponse
    {
        $mushroomAllocation->load(['quota', 'household', 'followups']);

        return response()->json($mushroomAllocation);
    }

    public function update(Request $request, MushroomAllocation $mushroomAllocation): JsonResponse
    {
        $validated = $request->validate([
            'bags'           => ['sometimes', 'integer', 'min:1'],
            'allocated_date' => ['nullable', 'date'],
            'status'         => ['nullable', 'in:pending,active,completed'],
            'note'           => ['nullable', 'string'],
        ]);

        $mushroomAllocation->update($validated);

        return response()->json($mushroomAllocation);
    }

    public function destroy(MushroomAllocation $mushroomAllocation): JsonResponse
    {
        if ($mushroomAllocation->followups()->exists()) {
            return response()->json(['message' => 'ไม่สามารถลบได้ มีข้อมูลติดตามผลแล้ว'], 422);
        }

        $mushroomAllocation->delete();

        return response()->json(['message' => 'ลบการจัดสรรสำเร็จ']);
    }
}
