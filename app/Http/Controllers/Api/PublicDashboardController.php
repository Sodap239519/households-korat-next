<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate-only stats for the public landing.
 * Never exposes individual household / personal data.
 */
class PublicDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'overview'    => $this->households(),
            'mushroom'    => $this->mushroom(),
            'tracking'    => $this->tracking(),
            'marketing'   => $this->marketing(),
            'meta'        => [
                'generated_at' => now()->toIso8601String(),
                'province'     => 'นครราชสีมา',
            ],
        ]);
    }

    private function households(): array
    {
        $hh = DB::table('households')->whereNull('deleted_at');

        $summary = [
            'total_districts'    => (clone $hh)->whereNotNull('district')->distinct()->count('district'),
            'total_subdistricts' => (clone $hh)->whereNotNull('sub_district')->distinct()->count('sub_district'),
            'total_households'   => (clone $hh)->count(),
            'passed'             => (clone $hh)->where('passed', 1)->count(),
            'failed'             => (clone $hh)->where('passed', 0)->count(),
            'avg_total_score'    => round((float) (clone $hh)->avg('total_score'), 2),
        ];

        $priorityCounts = (clone $hh)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->whereNotNull('priority')
            ->groupBy('priority')
            ->orderBy('priority')
            ->get();

        $byDistrict = (clone $hh)
            ->select(
                'district',
                DB::raw('COUNT(*) as households'),
                DB::raw('COALESCE(SUM(income_month), 0)  as total_income'),
                DB::raw('COALESCE(SUM(expense_month), 0) as total_expense'),
                DB::raw('COALESCE(SUM(debt_amount), 0)   as total_debt')
            )
            ->whereNotNull('district')
            ->groupBy('district')
            ->orderByDesc('households')
            ->limit(20)
            ->get();

        $priorityByDistrict = (clone $hh)
            ->select('district', 'priority', DB::raw('COUNT(*) as count'))
            ->whereNotNull('district')
            ->whereNotNull('priority')
            ->groupBy('district', 'priority')
            ->orderBy('district')
            ->get();

        return compact('summary', 'priorityCounts', 'byDistrict', 'priorityByDistrict');
    }

    private function mushroom(): array
    {
        $summary = [
            'total_quotas'         => DB::table('mushroom_quota_districts')->count(),
            'total_allocations'    => DB::table('mushroom_allocations')->count(),
            'total_followups'      => DB::table('mushroom_followups')->count(),
            'total_bags_quota'     => (int) DB::table('mushroom_quota_districts')->sum('quota_bags'),
            'total_bags_allocated' => (int) DB::table('mushroom_allocations')->sum('bags'),
            'total_harvest_kg'     => (float) DB::table('mushroom_followups')->sum('harvest_kg'),
            'total_sold_kg'        => (float) DB::table('mushroom_followups')->sum('sold_kg'),
            'total_revenue'        => (float) DB::table('mushroom_followups')->sum('revenue'),
        ];

        $quotaVsAllocated = DB::table('vw_mushroom_quota_vs_allocated')
            ->orderBy('district')
            ->get();

        $byDistrict = DB::table('vw_mushroom_revenue_by_district')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get();

        return compact('summary', 'quotaVsAllocated', 'byDistrict');
    }

    private function tracking(): array
    {
        $byChannel = DB::table('mushroom_followups')
            ->select('sale_channel', DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(sold_kg), 0) as total_sold_kg'),
                DB::raw('COALESCE(SUM(revenue), 0) as total_revenue'))
            ->whereNotNull('sale_channel')
            ->groupBy('sale_channel')
            ->get();

        $monthly = DB::table('mushroom_followups')
            ->select(
                DB::raw("DATE_FORMAT(followup_date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(harvest_kg), 0) as total_harvest_kg'),
                DB::raw('COALESCE(SUM(sold_kg), 0) as total_sold_kg'),
                DB::raw('COALESCE(SUM(revenue), 0) as total_revenue')
            )
            ->whereNotNull('followup_date')
            ->groupBy('month')
            ->orderBy('month')
            ->limit(24)
            ->get();

        $totals = [
            'total_followups' => DB::table('mushroom_followups')->count(),
            'months_with_data' => count($monthly),
        ];

        return compact('totals', 'byChannel', 'monthly');
    }

    private function marketing(): array
    {
        $byEnterprise = DB::table('vw_mushroom_revenue_by_enterprise')
            ->orderByDesc('total_revenue')
            ->limit(15)
            ->get();

        $totals = [
            'total_enterprise_count' => DB::table('mushroom_followups')
                ->where('enterprise_member', 1)
                ->whereNotNull('enterprise_name')
                ->distinct()->count('enterprise_name'),
            'total_revenue'          => (float) DB::table('mushroom_followups')->sum('revenue'),
            'enterprise_revenue'     => (float) DB::table('mushroom_followups')
                ->where('enterprise_member', 1)
                ->sum('revenue'),
        ];

        return compact('totals', 'byEnterprise');
    }
}
