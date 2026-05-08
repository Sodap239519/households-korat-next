<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ภาพรวมครัวเรือน (Tab 1)
    public function householdsOverview(): JsonResponse
    {
        $base = DB::table('households')->whereNull('deleted_at');

        $summary = [
            'total_households' => (clone $base)->count(),
            'passed'           => (clone $base)->where('passed', 1)->count(),
            'failed'           => (clone $base)->where('passed', 0)->count(),
            'avg_total_score'  => round((float) (clone $base)->avg('total_score'), 2),
            'total_districts'  => (clone $base)->whereNotNull('district')->distinct()->count('district'),
            'total_subdistricts' => (clone $base)->whereNotNull('sub_district')->distinct()->count('sub_district'),
        ];

        $priorityCounts = (clone $base)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->whereNotNull('priority')
            ->groupBy('priority')
            ->orderBy('priority')
            ->get();

        $byDistrict = (clone $base)
            ->select(
                'district',
                DB::raw('COUNT(*) as households'),
                DB::raw('COALESCE(SUM(income_month), 0)  as total_income'),
                DB::raw('COALESCE(SUM(expense_month), 0) as total_expense'),
                DB::raw('COALESCE(SUM(debt_amount), 0)   as total_debt')
            )
            ->whereNotNull('district')
            ->groupBy('district')
            ->orderByDesc('total_debt')
            ->get();

        $priorityByDistrict = (clone $base)
            ->select('district', 'priority', DB::raw('COUNT(*) as count'))
            ->whereNotNull('district')
            ->whereNotNull('priority')
            ->groupBy('district', 'priority')
            ->orderBy('district')
            ->orderBy('priority')
            ->get();

        return response()->json(compact('summary', 'priorityCounts', 'byDistrict', 'priorityByDistrict'));
    }

    // ภาพรวม Dashboard (เพาะเห็ด)
    public function dashboard(): JsonResponse
    {
        $summary = [
            'total_households'   => DB::table('households')->whereNull('deleted_at')->count(),
            'total_quotas'       => DB::table('mushroom_quota_districts')->count(),
            'total_allocations'  => DB::table('mushroom_allocations')->count(),
            'total_followups'    => DB::table('mushroom_followups')->count(),
            'total_bags_quota'   => (int) DB::table('mushroom_quota_districts')->sum('quota_bags'),
            'total_bags_allocated' => (int) DB::table('mushroom_allocations')->sum('bags'),
            'total_harvest_kg'   => (float) DB::table('mushroom_followups')->sum('harvest_kg'),
            'total_sold_kg'      => (float) DB::table('mushroom_followups')->sum('sold_kg'),
            'total_revenue'      => (float) DB::table('mushroom_followups')->sum('revenue'),
        ];

        return response()->json($summary);
    }

    // รายงานตามอำเภอ
    public function byDistrict(Request $request): JsonResponse
    {
        $query = DB::table('vw_mushroom_revenue_by_district');

        if ($province = $request->input('province')) {
            $query->where('province', $province);
        }

        $data = $query->orderByDesc('total_revenue')->get();

        return response()->json($data);
    }

    // รายงานโควต้า vs จัดสรร
    public function quotaVsAllocated(Request $request): JsonResponse
    {
        $query = DB::table('vw_mushroom_quota_vs_allocated');

        if ($year = $request->input('year')) {
            $query->where('year', $year);
        }
        if ($district = $request->input('district')) {
            $query->where('district', $district);
        }

        $data = $query->orderBy('year', 'desc')->orderBy('round')->orderBy('district')->get();

        return response()->json($data);
    }

    // รายงานรายได้ครัวเรือน
    public function householdRevenue(Request $request): JsonResponse
    {
        $query = DB::table('vw_mushroom_household_revenue');

        if ($district = $request->input('district')) {
            $query->where('district', $district);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('household_code', 'like', "%{$search}%");
            });
        }

        $data = $query->orderByDesc('total_revenue')->paginate($request->input('per_page', 20));

        return response()->json($data);
    }

    /**
     * รายงานเปรียบเทียบรายได้
     * เปรียบ "รายได้สำรวจ/เดือน" (households.income_month) กับ "รายได้รวมจากการขายเห็ด"
     * เป้าหมายระบบ: ทุกครัวเรือนต้องมีรายได้เพิ่มขึ้น ≥ 15%
     * สูตร: (sales_revenue - survey_income) / survey_income * 100
     */
    public function incomeComparison(Request $request): JsonResponse
    {
        $TARGET_PCT = 15.0;
        $year   = $request->input('year') ? (int) $request->input('year') : null;
        $search = trim((string) $request->input('search', ''));

        // Sum revenue per household from mushroom followups (filtered by year if provided)
        $followupBase = DB::table('mushroom_followups as mf')
            ->join('mushroom_allocations as ma', 'mf.allocation_id', '=', 'ma.id');
        if ($year) {
            $followupBase->join('mushroom_quota_districts as mq', 'ma.quota_id', '=', 'mq.id')
                         ->where('mq.year', $year);
        }
        $revenueByHousehold = (clone $followupBase)
            ->select('ma.household_id', DB::raw('COALESCE(SUM(mf.revenue), 0) as sales_revenue'))
            ->groupBy('ma.household_id')
            ->get()
            ->keyBy('household_id');

        $households = DB::table('households')
            ->whereNull('deleted_at')
            ->select('id', 'household_code', 'prefix', 'first_name', 'last_name',
                     'district', 'sub_district', 'house_number', 'income_month')
            ->get();

        $rows = $households->map(function ($h) use ($revenueByHousehold, $TARGET_PCT) {
            $survey = (float) ($h->income_month ?? 0);
            $sales  = (float) ($revenueByHousehold->get($h->id)->sales_revenue ?? 0);
            $diff   = $sales - $survey;

            // % เพิ่ม:
            //   - มีรายได้สำรวจ (>0): สูตรปกติ (sales - survey) / survey * 100
            //   - ไม่มีรายได้สำรวจ (=0) แต่มีการขาย: ถือว่าเป็นรายได้ใหม่ ผ่านเป้าทันที (set 100%)
            //   - ไม่มีทั้งสำรวจและการขาย: คำนวณไม่ได้ (null)
            $pct = null;
            $isNewIncome = false;
            if ($survey > 0) {
                $pct = round($diff / $survey * 100, 2);
            } elseif ($sales > 0) {
                $pct = 100.0;
                $isNewIncome = true;
            }

            return [
                'household_id'    => $h->id,
                'household_code'  => $h->household_code,
                'name'            => trim(($h->prefix ?? '') . ' ' . $h->first_name . ' ' . $h->last_name),
                'district'        => $h->district,
                'sub_district'    => $h->sub_district,
                'house_number'    => $h->house_number,
                'survey_income'   => $survey,
                'sales_revenue'   => $sales,
                'increase_amount' => $diff,
                'increase_pct'    => $pct,
                'is_new_income'   => $isNewIncome,
                'passed_target'   => $pct !== null && $pct >= $TARGET_PCT,
                'has_sales'       => $sales > 0,
            ];
        });

        // District summary (unchanged by search)
        $byDistrict = $rows->groupBy('district')->map(function ($group, $district) use ($TARGET_PCT) {
            $totalSurvey = (float) $group->sum('survey_income');
            $totalSales  = (float) $group->sum('sales_revenue');
            $diff = $totalSales - $totalSurvey;
            $pct = null;
            $isNewIncome = false;
            if ($totalSurvey > 0) {
                $pct = round($diff / $totalSurvey * 100, 2);
            } elseif ($totalSales > 0) {
                $pct = 100.0;
                $isNewIncome = true;
            }
            return [
                'district'             => $district,
                'households_count'     => $group->count(),
                'with_sales_count'     => $group->where('has_sales', true)->count(),
                'passed_count'         => $group->where('passed_target', true)->count(),
                'total_survey_income'  => $totalSurvey,
                'total_sales_revenue'  => $totalSales,
                'increase_amount'      => $diff,
                'increase_pct'         => $pct,
                'is_new_income'        => $isNewIncome,
                'passed_target'        => $pct !== null && $pct >= $TARGET_PCT,
            ];
        })->values()->sortByDesc('increase_pct')->values();

        // Per-household: only those with sales, then apply search across name/house_number/district/code
        $perHousehold = $rows->where('has_sales', true);

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $perHousehold = $perHousehold->filter(function ($r) use ($needle) {
                foreach (['name', 'household_code', 'district', 'house_number'] as $f) {
                    if (mb_strpos(mb_strtolower((string) ($r[$f] ?? '')), $needle) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        $perHousehold = $perHousehold->sortByDesc('increase_pct')->values();

        // Summary based on the unfiltered set
        $totalSurvey = (float) $rows->sum('survey_income');
        $totalSales  = (float) $rows->sum('sales_revenue');
        $overallDiff = $totalSales - $totalSurvey;
        $overallPct  = $totalSurvey > 0 ? round($overallDiff / $totalSurvey * 100, 2) : null;
        $withSales   = $rows->where('has_sales', true);
        $passed      = $rows->where('passed_target', true);

        $summary = [
            'target_pct'           => $TARGET_PCT,
            'total_households'     => $rows->count(),
            'with_sales'           => $withSales->count(),
            'passed_target'        => $passed->count(),
            'failed_target'        => $withSales->filter(fn ($r) => ! $r['passed_target'])->count(),
            'total_survey_income'  => $totalSurvey,
            'total_sales_revenue'  => $totalSales,
            'increase_amount'      => $overallDiff,
            'overall_increase_pct' => $overallPct,
            'avg_household_pct'    => round((float) $withSales->avg('increase_pct'), 2),
        ];

        return response()->json([
            'summary'       => $summary,
            'byDistrict'    => $byDistrict,
            'byHousehold'   => $perHousehold,
            'matched_count' => $perHousehold->count(),
        ]);
    }

    // รายงานวิสาหกิจ
    /**
     * รายงาน "กลุ่มเพาะเห็ด" — รวมข้อมูลตาม group_code ของการจัดสรรแบบกลุ่ม
     * แสดงครัวเรือน, ก้อนรวม, ผลผลิต, ขาย, รายได้ ของแต่ละกลุ่ม
     */
    public function byGroup(): JsonResponse
    {
        $data = DB::table('mushroom_allocations as ma')
            ->join('mushroom_quota_districts as mq', 'ma.quota_id', '=', 'mq.id')
            ->leftJoin('mushroom_followups as mf', 'mf.allocation_id', '=', 'ma.id')
            ->whereNotNull('ma.group_code')
            ->select(
                'ma.group_code',
                DB::raw('MAX(ma.group_label) as group_label'),
                DB::raw('MAX(mq.district)    as district'),
                DB::raw('MAX(mq.year)        as year'),
                DB::raw('MIN(ma.allocated_date) as first_allocated_date'),
                DB::raw('COUNT(DISTINCT ma.household_id) as households_count'),
                DB::raw('SUM(DISTINCT ma.bags) as total_bags'),
                DB::raw('COALESCE(SUM(mf.harvest_kg), 0) as total_harvest_kg'),
                DB::raw('COALESCE(SUM(mf.sold_kg), 0)    as total_sold_kg'),
                DB::raw('COALESCE(SUM(mf.revenue), 0)    as total_revenue'),
            )
            ->groupBy('ma.group_code')
            ->orderByDesc('total_revenue')
            ->get();

        // SUM(DISTINCT bags) won't work right when 2 members happen to have the same bags;
        // recompute total_bags properly via a separate aggregation.
        $bagSums = DB::table('mushroom_allocations')
            ->whereNotNull('group_code')
            ->select('group_code', DB::raw('SUM(bags) as bags_sum'))
            ->groupBy('group_code')
            ->pluck('bags_sum', 'group_code');

        $data = $data->map(function ($row) use ($bagSums) {
            $row->total_bags = (int) ($bagSums[$row->group_code] ?? 0);
            return $row;
        });

        return response()->json($data);
    }

    public function byEnterprise(): JsonResponse
    {
        $data = DB::table('vw_mushroom_revenue_by_enterprise')
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json($data);
    }

    // รายการปี พ.ศ. ที่มีข้อมูล
    public function years(): JsonResponse
    {
        $years = DB::table('mushroom_quota_districts')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return response()->json($years);
    }

    // รายการอำเภอ
    public function districts(): JsonResponse
    {
        $districts = DB::table('mushroom_quota_districts')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');

        return response()->json($districts);
    }
}
