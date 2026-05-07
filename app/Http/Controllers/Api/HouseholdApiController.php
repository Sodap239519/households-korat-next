<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HouseholdApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Household::query()
            ->leftJoin('vw_mushroom_household_revenue as v', 'v.household_id', '=', 'households.id')
            ->select(
                'households.*',
                DB::raw('COALESCE(v.total_bags_received, 0) as total_bags_received'),
                DB::raw('COALESCE(v.total_harvest_kg, 0)    as total_harvest_kg'),
                DB::raw('COALESCE(v.total_sold_kg, 0)       as total_sold_kg'),
                DB::raw('COALESCE(v.total_revenue, 0)       as total_revenue'),
                DB::raw('COALESCE(v.allocation_count, 0)    as allocation_count'),
                DB::raw('COALESCE(v.followup_count, 0)      as followup_count'),
            );

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('households.household_code', 'like', "%{$search}%")
                  ->orWhere('households.first_name', 'like', "%{$search}%")
                  ->orWhere('households.last_name',  'like', "%{$search}%")
                  ->orWhere('households.id_card',    'like', "%{$search}%");
            });
        }
        if ($district = $request->input('district')) $query->where('households.district', $district);
        if ($priority = $request->input('priority')) $query->where('households.priority', $priority);
        if ($request->has('passed') && $request->input('passed') !== '') {
            $query->where('households.passed', (int) $request->input('passed'));
        }

        return response()->json(
            $query->orderBy('households.household_code')->paginate($request->input('per_page', 20))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['recorded_by'] = $request->user()->id;
        $household = Household::create($validated);

        AdminNotificationService::householdCreated($household, $request->user());

        return response()->json($household, 201);
    }

    public function show(Household $household): JsonResponse
    {
        return response()->json($household);
    }

    /**
     * Detailed tracking view for a single household:
     * basic info + all allocations (with quota district/year/round)
     * + each allocation's followups (harvest, sold, revenue, channel, enterprise).
     */
    public function tracking(Household $household): JsonResponse
    {
        $household->load([
            'allocations.quota',
            'allocations.followups' => function ($q) {
                $q->orderBy('followup_round');
            },
        ]);

        // Aggregate totals straight from the helper view
        $totals = DB::table('vw_mushroom_household_revenue')
            ->where('household_id', $household->id)
            ->first();

        $enterprises = DB::table('mushroom_followups as mf')
            ->join('mushroom_allocations as ma', 'mf.allocation_id', '=', 'ma.id')
            ->where('ma.household_id', $household->id)
            ->where('mf.enterprise_member', 1)
            ->whereNotNull('mf.enterprise_name')
            ->where('mf.enterprise_name', '!=', '')
            ->distinct()
            ->pluck('mf.enterprise_name');

        return response()->json([
            'household'   => $household,
            'totals'      => $totals,
            'enterprises' => $enterprises,
        ]);
    }

    public function update(Request $request, Household $household): JsonResponse
    {
        $validated = $this->validatePayload($request, $household->id);
        $household->update($validated);
        return response()->json($household->fresh());
    }

    public function destroy(Household $household): JsonResponse
    {
        $household->delete();
        return response()->json(['message' => 'ลบข้อมูลครัวเรือนสำเร็จ']);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $codeUnique    = Rule::unique('households', 'household_code');
        $idCardUnique  = Rule::unique('households', 'id_card');
        if ($ignoreId) {
            $codeUnique->ignore($ignoreId);
            $idCardUnique->ignore($ignoreId);
        }

        return $request->validate([
            // Section 1: ข้อมูลครัวเรือน
            'household_code'    => ['required', 'string', 'max:50', $codeUnique],
            'province'          => ['nullable', 'string', 'max:100'],
            'district'          => ['nullable', 'string', 'max:100'],
            'sub_district'      => ['nullable', 'string', 'max:100'],
            'moo_number'        => ['nullable', 'string', 'max:50'],
            'village'           => ['nullable', 'string', 'max:100'],
            'house_number'      => ['nullable', 'string', 'max:50'],
            'postal_code'       => ['nullable', 'string', 'max:10'],
            'head_full_name'    => ['nullable', 'string', 'max:200'],
            'members_count'     => ['nullable', 'integer', 'min:1', 'max:100'],

            // Section 2: ข้อมูลผู้เปราะบาง
            'prefix'            => ['nullable', 'string', 'max:20'],
            'first_name'        => ['required', 'string', 'max:100'],
            'last_name'         => ['required', 'string', 'max:100'],
            'id_card'           => ['nullable', 'string', 'max:13', $idCardUnique],
            'gender'            => ['nullable', 'string', 'max:20'],
            'dob'               => ['nullable', 'date'],
            'age'               => ['nullable', 'integer', 'min:0', 'max:120'],
            'phone'             => ['nullable', 'string', 'max:20'],
            'education'         => ['nullable', 'string', 'max:100'],
            'health'            => ['nullable', 'string', 'max:200'],

            // Section 3: เศรษฐกิจ
            'main_occupation'      => ['nullable', 'string', 'max:200'],
            'secondary_occupation' => ['nullable', 'string', 'max:200'],
            'income_month'         => ['nullable', 'numeric', 'min:0'],
            'expense_month'        => ['nullable', 'numeric', 'min:0'],
            'debt_amount'          => ['nullable', 'numeric', 'min:0'],
            'debt_source'          => ['nullable', 'string', 'max:200'],

            // Section 4: เห็ด/เกษตร
            'has_mushroom_area'    => ['boolean'],
            'mushroom_area_size'   => ['nullable', 'numeric', 'min:0'],
            'water_source'         => ['nullable', 'string', 'max:100'],
            'has_electricity'      => ['boolean'],
            'distance_to_market_km'=> ['nullable', 'numeric', 'min:0'],
            'ever_agriculture'     => ['boolean'],
            'ever_mushroom'        => ['boolean'],
            'smartphone_use'       => ['nullable', 'string', 'max:100'],
            'social_media_use'     => ['boolean'],
            'interest_level'       => ['nullable', 'string', 'max:100'],
            'interest_reason'      => ['nullable', 'string'],
            'hours_per_week'       => ['nullable', 'integer', 'min:0', 'max:168'],
            'initial_investment'   => ['nullable', 'numeric', 'min:0'],
            'group_member'         => ['boolean'],
            'group_readiness'      => ['nullable', 'string', 'max:100'],

            // Section 5: คะแนนประเมิน
            'poverty_score'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'motivation_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'experience_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'grouping_score'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'potential_score'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'area_score'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'market_score'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_score'      => ['nullable', 'numeric', 'min:0'],
            'priority'         => ['nullable', Rule::in(['A', 'B', 'C', 'D'])],
            'passed'           => ['boolean'],
            'completed'        => ['boolean'],

            // Section 6: ผู้สำรวจ + meta
            'survey_date'      => ['nullable', 'date'],
            'surveyor'         => ['nullable', 'string', 'max:200'],
            'is_active'        => ['boolean'],
            'note'             => ['nullable', 'string'],
        ]);
    }
}
