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

    // รายงานวิสาหกิจ
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
