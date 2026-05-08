<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MushroomAllocation;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MushroomAllocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Compute per-household allocation sequence with a SQL window function
        // so we can filter by it server-side (?round=N means "Nth allocation
        // for this household").
        $rankedSub = DB::table('mushroom_allocations')
            ->selectRaw('id, ROW_NUMBER() OVER (PARTITION BY household_id ORDER BY COALESCE(allocated_date, "9999-12-31"), id) as allocation_round');

        $query = MushroomAllocation::query()
            ->with(['quota', 'household'])
            ->joinSub($rankedSub, 'rank', function ($j) {
                $j->on('mushroom_allocations.id', '=', 'rank.id');
            })
            ->select('mushroom_allocations.*', 'rank.allocation_round');

        if ($quotaId     = $request->input('quota_id'))     $query->where('mushroom_allocations.quota_id',     $quotaId);
        if ($householdId = $request->input('household_id')) $query->where('mushroom_allocations.household_id', $householdId);
        if ($status      = $request->input('status'))       $query->where('mushroom_allocations.status',       $status);

        // District / year still come from the related quota
        if ($district = $request->input('district')) {
            $query->whereHas('quota', fn ($q) => $q->where('district', $district));
        }
        if ($year = $request->input('year')) {
            $query->whereHas('quota', fn ($q) => $q->where('year', $year));
        }

        // ?round= now means allocation_round (Nth time this household received).
        if ($round = $request->input('round')) {
            $query->where('rank.allocation_round', $round);
        }

        $allocations = $query->orderBy('mushroom_allocations.allocated_date', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($allocations);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->canCreateAllocation(), 403, 'ไม่มีสิทธิ์จัดสรรโควต้า');

        $validated = $request->validate([
            'quota_id'       => ['required', 'exists:mushroom_quota_districts,id'],
            'household_id'   => ['required', 'exists:households,id'],
            'bags'           => ['required', 'integer', 'min:1'],
            'allocated_date' => ['nullable', 'date'],
            'status'         => ['nullable', 'in:pending,active,completed'],
            'note'           => ['nullable', 'string'],
        ]);

        // ตรวจสอบโควต้าคงเหลือ
        $quota = \App\Models\MushroomQuotaDistrict::lockForUpdate()->findOrFail($validated['quota_id']);

        abort_unless(
            $request->user()->canActInDistrict($quota->district),
            403,
            "ไม่มีสิทธิ์จัดสรรในอำเภอ {$quota->district} (เกินเขตพื้นที่ที่คุณดูแล)",
        );
        $allocated = $quota->allocations()->sum('bags');
        $remaining = $quota->quota_bags - $allocated;

        if ($validated['bags'] > $remaining) {
            return response()->json([
                'message' => "โควต้าไม่เพียงพอ (คงเหลือ: {$remaining} ถุง)",
            ], 422);
        }

        $allocation = MushroomAllocation::create($validated);
        $allocation->load(['quota', 'household']);

        AdminNotificationService::allocationCreated($allocation, $request->user());

        return response()->json($allocation, 201);
    }

    public function show(MushroomAllocation $mushroomAllocation): JsonResponse
    {
        $mushroomAllocation->load(['quota', 'household', 'followups']);

        return response()->json($mushroomAllocation);
    }

    public function update(Request $request, MushroomAllocation $mushroomAllocation): JsonResponse
    {
        abort_unless($request->user()->canCreateAllocation(), 403, 'ไม่มีสิทธิ์แก้ไขการจัดสรร');
        $district = $mushroomAllocation->quota?->district;
        abort_unless(
            $request->user()->canActInDistrict($district),
            403,
            "ไม่มีสิทธิ์แก้ไขในอำเภอ {$district} (เกินเขตพื้นที่ที่คุณดูแล)",
        );

        $validated = $request->validate([
            'bags'           => ['sometimes', 'integer', 'min:1'],
            'allocated_date' => ['nullable', 'date'],
            'status'         => ['nullable', 'in:pending,active,completed'],
            'note'           => ['nullable', 'string'],
        ]);

        $mushroomAllocation->update($validated);

        return response()->json($mushroomAllocation);
    }

    /**
     * Group allocation: split a single bag total evenly across many households,
     * tagging each child allocation with the same group_code so they remain
     * linked. Production / revenue can later be split via the matching group
     * followup endpoint.
     */
    public function storeGroup(Request $request): JsonResponse
    {
        abort_unless($request->user()->canCreateAllocation(), 403, 'ไม่มีสิทธิ์จัดสรรโควต้า');

        $validated = $request->validate([
            'quota_id'        => ['required', 'exists:mushroom_quota_districts,id'],
            'household_ids'   => ['required', 'array', 'min:1'],
            'household_ids.*' => ['integer', 'exists:households,id'],
            'total_bags'      => ['required', 'integer', 'min:1'],
            'allocated_date'  => ['nullable', 'date'],
            'status'          => ['nullable', 'in:pending,active,completed'],
            'note'            => ['nullable', 'string'],
            'group_label'     => ['nullable', 'string', 'max:120'],
        ]);

        $quota = \App\Models\MushroomQuotaDistrict::lockForUpdate()->findOrFail($validated['quota_id']);

        abort_unless(
            $request->user()->canActInDistrict($quota->district),
            403,
            "ไม่มีสิทธิ์จัดสรรในอำเภอ {$quota->district} (เกินเขตพื้นที่ที่คุณดูแล)",
        );

        $allocated = $quota->allocations()->sum('bags');
        $remaining = $quota->quota_bags - $allocated;
        if ($validated['total_bags'] > $remaining) {
            return response()->json([
                'message' => "โควต้าไม่เพียงพอ (คงเหลือ: {$remaining} ก้อน)",
            ], 422);
        }

        // Even split — integer per household, leftover added to the first N
        $ids = array_values(array_unique($validated['household_ids']));
        $n = count($ids);
        $base = intdiv($validated['total_bags'], $n);
        $extra = $validated['total_bags'] - ($base * $n); // 0..n-1 households get +1

        if ($base < 1) {
            return response()->json([
                'message' => "จำนวนก้อนรวมน้อยกว่าจำนวนครัวเรือน — ต้องอย่างน้อย {$n} ก้อน",
            ], 422);
        }

        $code = (string) \Illuminate\Support\Str::uuid();
        $rows = [];
        foreach ($ids as $i => $hid) {
            $bags = $base + ($i < $extra ? 1 : 0);
            $rows[] = MushroomAllocation::create([
                'quota_id'       => $validated['quota_id'],
                'household_id'   => $hid,
                'group_code'     => $code,
                'group_label'    => $validated['group_label'] ?? null,
                'bags'           => $bags,
                'allocated_date' => $validated['allocated_date'] ?? null,
                'status'         => $validated['status'] ?? 'pending',
                'note'           => $validated['note'] ?? null,
            ]);
        }

        // Notify admins once for the whole group (use the first row as a representative)
        if (!empty($rows)) {
            $rep = $rows[0]->load(['quota', 'household']);
            AdminNotificationService::allocationCreated($rep, $request->user());
        }

        return response()->json([
            'message'      => "บันทึกการจัดสรรแบบกลุ่มสำเร็จ ({$n} ครัวเรือน, {$validated['total_bags']} ก้อน)",
            'group_code'   => $code,
            'group_label'  => $validated['group_label'] ?? null,
            'count'        => $n,
            'allocations'  => collect($rows)->map(fn ($r) => $r->only(['id','household_id','bags']))->all(),
        ], 201);
    }

    public function destroy(Request $request, MushroomAllocation $mushroomAllocation): JsonResponse
    {
        abort_unless($request->user()->canCreateAllocation(), 403, 'ไม่มีสิทธิ์ลบการจัดสรร');
        $district = $mushroomAllocation->quota?->district;
        abort_unless(
            $request->user()->canActInDistrict($district),
            403,
            "ไม่มีสิทธิ์ลบในอำเภอ {$district}",
        );

        if ($mushroomAllocation->followups()->exists()) {
            return response()->json(['message' => 'ไม่สามารถลบได้ มีข้อมูลติดตามผลแล้ว'], 422);
        }

        $mushroomAllocation->delete();

        return response()->json(['message' => 'ลบการจัดสรรสำเร็จ']);
    }
}
