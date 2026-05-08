<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MushroomFollowup;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MushroomFollowupController extends Controller
{
    /**
     * Suggestions for free-text fields (sale_place, enterprise_name).
     * Returns distinct, non-empty values that have already been entered.
     * Used by the followup form's AutoComplete inputs.
     */
    public function suggestions(): JsonResponse
    {
        $salePlaces = DB::table('mushroom_followups')
            ->whereNotNull('sale_place')
            ->where('sale_place', '!=', '')
            ->distinct()
            ->orderBy('sale_place')
            ->pluck('sale_place')
            ->values();

        $enterprises = DB::table('mushroom_followups')
            ->whereNotNull('enterprise_name')
            ->where('enterprise_name', '!=', '')
            ->distinct()
            ->orderBy('enterprise_name')
            ->pluck('enterprise_name')
            ->values();

        return response()->json([
            'sale_places' => $salePlaces,
            'enterprises' => $enterprises,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = MushroomFollowup::with([
            'allocation.household',
            'allocation.quota',
        ]);

        if ($allocationId = $request->input('allocation_id')) $query->where('allocation_id', $allocationId);
        if ($channel = $request->input('sale_channel'))       $query->where('sale_channel',  $channel);

        // Filter by household across all of that household's allocations
        if ($householdId = $request->input('household_id')) {
            $query->whereHas('allocation', fn ($q) => $q->where('household_id', $householdId));
        }

        // Filter through allocation -> quota
        if ($district = $request->input('district')) {
            $query->whereHas('allocation.quota', fn ($q) => $q->where('district', $district));
        }
        if ($year = $request->input('year')) {
            $query->whereHas('allocation.quota', fn ($q) => $q->where('year', $year));
        }
        if ($round = $request->input('round')) {
            $query->whereHas('allocation.quota', fn ($q) => $q->where('round', $round));
        }

        $followups = $query->orderBy('followup_date', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($followups);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->canCreateFollowup(), 403, 'ไม่มีสิทธิ์บันทึกการติดตามผล');

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

        // ตรวจสิทธิ์ตามเขตพื้นที่ผ่านความสัมพันธ์ allocation -> quota
        $allocation = \App\Models\MushroomAllocation::with('quota')->findOrFail($validated['allocation_id']);
        $district = $allocation->quota?->district;
        abort_unless(
            $request->user()->canActInDistrict($district),
            403,
            "ไม่มีสิทธิ์บันทึกการติดตามในอำเภอ {$district}",
        );

        // คำนวณรายได้อัตโนมัติ
        if (empty($validated['revenue']) && !empty($validated['sold_kg']) && !empty($validated['price_per_kg'])) {
            $validated['revenue'] = (float) $validated['sold_kg'] * (float) $validated['price_per_kg'];
        }

        $followup = MushroomFollowup::create($validated);
        $followup->load(['allocation.household', 'allocation.quota']);

        AdminNotificationService::followupCreated($followup, $request->user());

        return response()->json($followup, 201);
    }

    public function show(MushroomFollowup $mushroomFollowup): JsonResponse
    {
        $mushroomFollowup->load(['allocation.household', 'allocation.quota']);

        return response()->json($mushroomFollowup);
    }

    public function update(Request $request, MushroomFollowup $mushroomFollowup): JsonResponse
    {
        abort_unless($request->user()->canCreateFollowup(), 403, 'ไม่มีสิทธิ์แก้ไขการติดตาม');
        $district = $mushroomFollowup->allocation?->quota?->district;
        abort_unless(
            $request->user()->canActInDistrict($district),
            403,
            "ไม่มีสิทธิ์แก้ไขการติดตามในอำเภอ {$district}",
        );

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

    /**
     * Group followup: split production / revenue evenly across all members of a
     * group_code, creating one followup per allocation. Useful when the group
     * sells together and books one consolidated harvest.
     */
    public function storeGroup(Request $request): JsonResponse
    {
        abort_unless($request->user()->canCreateFollowup(), 403, 'ไม่มีสิทธิ์บันทึกการติดตามผล');

        $validated = $request->validate([
            'group_code'        => ['required', 'string', 'size:36'],
            'followup_round'    => ['required', 'integer', 'min:1'],
            'followup_date'     => ['nullable', 'date'],
            'total_harvest_kg'  => ['nullable', 'numeric', 'min:0'],
            'total_sold_kg'     => ['nullable', 'numeric', 'min:0'],
            'price_per_kg'      => ['nullable', 'numeric', 'min:0'],
            'total_revenue'     => ['nullable', 'numeric', 'min:0'],
            'sale_channel'      => ['nullable', 'in:direct,online,enterprise,market'],
            'sale_place'        => ['nullable', 'string', 'max:255'],
            'enterprise_member' => ['boolean'],
            'enterprise_name'   => ['nullable', 'string', 'max:255'],
            'note'              => ['nullable', 'string'],
        ]);

        $allocations = \App\Models\MushroomAllocation::with('quota')
            ->where('group_code', $validated['group_code'])->get();

        if ($allocations->isEmpty()) {
            return response()->json(['message' => 'ไม่พบกลุ่มที่ระบุ'], 404);
        }

        $district = $allocations->first()->quota?->district;
        abort_unless(
            $request->user()->canActInDistrict($district),
            403,
            "ไม่มีสิทธิ์บันทึกการติดตามในอำเภอ {$district}",
        );

        $n = $allocations->count();
        // Evenly split (kg / revenue can be fractional)
        $perHarvest = isset($validated['total_harvest_kg']) ? round((float) $validated['total_harvest_kg'] / $n, 2) : null;
        $perSold    = isset($validated['total_sold_kg'])    ? round((float) $validated['total_sold_kg']    / $n, 2) : null;
        $perRevenue = isset($validated['total_revenue'])    ? round((float) $validated['total_revenue']    / $n, 2) : null;

        // Auto-derive revenue from sold * price if needed
        if (is_null($perRevenue) && !is_null($perSold) && !empty($validated['price_per_kg'])) {
            $perRevenue = round($perSold * (float) $validated['price_per_kg'], 2);
        }

        $created = [];
        foreach ($allocations as $a) {
            $created[] = MushroomFollowup::create([
                'allocation_id'     => $a->id,
                'followup_round'    => $validated['followup_round'],
                'followup_date'     => $validated['followup_date']  ?? null,
                'harvest_kg'        => $perHarvest,
                'sold_kg'           => $perSold,
                'price_per_kg'      => $validated['price_per_kg']   ?? null,
                'revenue'           => $perRevenue,
                'sale_channel'      => $validated['sale_channel']   ?? null,
                'sale_place'        => $validated['sale_place']     ?? null,
                'enterprise_member' => $validated['enterprise_member'] ?? false,
                'enterprise_name'   => $validated['enterprise_name'] ?? null,
                'note'              => $validated['note']           ?? null,
            ]);
        }

        if (!empty($created)) {
            $rep = $created[0]->load(['allocation.household', 'allocation.quota']);
            AdminNotificationService::followupCreated($rep, $request->user());
        }

        return response()->json([
            'message'        => "บันทึกติดตามผลแบบกลุ่มสำเร็จ ({$n} ครัวเรือน)",
            'group_code'     => $validated['group_code'],
            'count'          => $n,
            'per_harvest_kg' => $perHarvest,
            'per_sold_kg'    => $perSold,
            'per_revenue'    => $perRevenue,
        ], 201);
    }

    public function destroy(Request $request, MushroomFollowup $mushroomFollowup): JsonResponse
    {
        abort_unless($request->user()->canCreateFollowup(), 403, 'ไม่มีสิทธิ์ลบ');
        $district = $mushroomFollowup->allocation?->quota?->district;
        abort_unless(
            $request->user()->canActInDistrict($district),
            403,
            "ไม่มีสิทธิ์ลบในอำเภอ {$district}",
        );

        $mushroomFollowup->delete();

        return response()->json(['message' => 'ลบข้อมูลติดตามผลสำเร็จ']);
    }
}
