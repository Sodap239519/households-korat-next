<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate-only stats for the public landing page.
 * Never exposes individual household details.
 */
class PublicDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $hh = DB::table('households')->whereNull('deleted_at');

        $summary = [
            'total_districts'     => (clone $hh)->whereNotNull('district')->distinct()->count('district'),
            'total_subdistricts'  => (clone $hh)->whereNotNull('sub_district')->distinct()->count('sub_district'),
            'total_households'    => (clone $hh)->count(),
            'passed'              => (clone $hh)->where('passed', 1)->count(),
            'failed'              => (clone $hh)->where('passed', 0)->count(),
            'total_quotas'        => DB::table('mushroom_quota_districts')->count(),
            'total_bags_quota'    => (int) DB::table('mushroom_quota_districts')->sum('quota_bags'),
            'total_revenue'       => (float) DB::table('mushroom_followups')->sum('revenue'),
        ];

        $priorityCounts = (clone $hh)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->whereNotNull('priority')
            ->groupBy('priority')
            ->orderBy('priority')
            ->get();

        $byDistrict = (clone $hh)
            ->select('district', DB::raw('COUNT(*) as households'))
            ->whereNotNull('district')
            ->groupBy('district')
            ->orderByDesc('households')
            ->limit(20)
            ->get();

        $revenueByDistrict = DB::table('vw_mushroom_revenue_by_district')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get();

        return response()->json(compact('summary', 'priorityCounts', 'byDistrict', 'revenueByDistrict'));
    }
}
