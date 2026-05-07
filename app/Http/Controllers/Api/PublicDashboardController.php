<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate-only stats for the public landing.
 * Never exposes individual household / personal data.
 */
class PublicDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = $request->input('year') ? (int) $request->input('year') : null;

        return response()->json([
            'overview'    => $this->households(),
            'mushroom'    => $this->mushroom($year),
            'tracking'    => $this->tracking($year),
            'marketing'   => $this->marketing($year),
            'meta'        => [
                'generated_at'  => now()->toIso8601String(),
                'province'      => 'นครราชสีมา',
                'selected_year' => $year,
            ],
        ]);
    }

    public function years(): JsonResponse
    {
        $years = DB::table('mushroom_quota_districts')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return response()->json($years);
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

    private function mushroom(?int $year): array
    {
        // Quotas — direct year column
        $quotaQ = DB::table('mushroom_quota_districts');
        if ($year) $quotaQ->where('year', $year);

        // Allocations + Followups — join through quota for year filter
        $allocQ = DB::table('mushroom_allocations as ma');
        $followQ = DB::table('mushroom_followups as mf')
            ->join('mushroom_allocations as ma', 'mf.allocation_id', '=', 'ma.id');
        if ($year) {
            $allocQ->join('mushroom_quota_districts as mq', 'ma.quota_id', '=', 'mq.id')
                   ->where('mq.year', $year);
            $followQ->join('mushroom_quota_districts as mq', 'ma.quota_id', '=', 'mq.id')
                    ->where('mq.year', $year);
        }

        $summary = [
            'total_quotas'         => (clone $quotaQ)->count(),
            'total_bags_quota'     => (int) (clone $quotaQ)->sum('quota_bags'),
            'total_allocations'    => (clone $allocQ)->count(),
            'total_bags_allocated' => (int) (clone $allocQ)->sum('ma.bags'),
            'total_followups'      => (clone $followQ)->count(),
            'total_harvest_kg'     => (float) (clone $followQ)->sum('mf.harvest_kg'),
            'total_sold_kg'        => (float) (clone $followQ)->sum('mf.sold_kg'),
            'total_revenue'        => (float) (clone $followQ)->sum('mf.revenue'),
        ];

        $quotaVsAllocated = DB::table('vw_mushroom_quota_vs_allocated');
        if ($year) $quotaVsAllocated->where('year', $year);
        $quotaVsAllocated = $quotaVsAllocated->orderBy('district')->get();

        $byDistrict = $year
            ? DB::table('mushroom_followups as mf')
                ->join('mushroom_allocations as ma', 'mf.allocation_id', '=', 'ma.id')
                ->join('mushroom_quota_districts as mq', 'ma.quota_id', '=', 'mq.id')
                ->where('mq.year', $year)
                ->select('mq.district',
                    DB::raw('COALESCE(SUM(mf.harvest_kg), 0) as total_harvest_kg'),
                    DB::raw('COALESCE(SUM(mf.sold_kg), 0)    as total_sold_kg'),
                    DB::raw('COALESCE(SUM(mf.revenue), 0)    as total_revenue'))
                ->groupBy('mq.district')
                ->orderByDesc('total_revenue')
                ->limit(20)
                ->get()
            : DB::table('vw_mushroom_revenue_by_district')
                ->orderByDesc('total_revenue')
                ->limit(20)
                ->get();

        return compact('summary', 'quotaVsAllocated', 'byDistrict');
    }

    private function tracking(?int $year): array
    {
        $base = DB::table('mushroom_followups as mf');
        if ($year) {
            $base->join('mushroom_allocations as ma', 'mf.allocation_id', '=', 'ma.id')
                 ->join('mushroom_quota_districts as mq', 'ma.quota_id', '=', 'mq.id')
                 ->where('mq.year', $year);
        }

        $byChannel = (clone $base)
            ->select('mf.sale_channel as sale_channel',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(mf.sold_kg), 0) as total_sold_kg'),
                DB::raw('COALESCE(SUM(mf.revenue), 0) as total_revenue'))
            ->whereNotNull('mf.sale_channel')
            ->groupBy('mf.sale_channel')
            ->get();

        $monthly = (clone $base)
            ->select(
                DB::raw("DATE_FORMAT(mf.followup_date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(mf.harvest_kg), 0) as total_harvest_kg'),
                DB::raw('COALESCE(SUM(mf.sold_kg), 0) as total_sold_kg'),
                DB::raw('COALESCE(SUM(mf.revenue), 0) as total_revenue')
            )
            ->whereNotNull('mf.followup_date')
            ->groupBy('month')
            ->orderBy('month')
            ->limit(24)
            ->get();

        $totals = [
            'total_followups'  => (clone $base)->count(),
            'months_with_data' => count($monthly),
        ];

        return compact('totals', 'byChannel', 'monthly');
    }

    private function marketing(?int $year): array
    {
        if ($year) {
            $byEnterprise = DB::table('mushroom_followups as mf')
                ->join('mushroom_allocations as ma', 'mf.allocation_id', '=', 'ma.id')
                ->join('mushroom_quota_districts as mq', 'ma.quota_id', '=', 'mq.id')
                ->where('mq.year', $year)
                ->where('mf.enterprise_member', 1)
                ->whereNotNull('mf.enterprise_name')
                ->select('mf.enterprise_name',
                    DB::raw('COALESCE(SUM(mf.sold_kg), 0) as total_sold_kg'),
                    DB::raw('COALESCE(SUM(mf.revenue), 0) as total_revenue'))
                ->groupBy('mf.enterprise_name')
                ->orderByDesc('total_revenue')
                ->limit(15)
                ->get();
        } else {
            $byEnterprise = DB::table('vw_mushroom_revenue_by_enterprise')
                ->orderByDesc('total_revenue')
                ->limit(15)
                ->get();
        }

        $followQ = DB::table('mushroom_followups as mf');
        if ($year) {
            $followQ->join('mushroom_allocations as ma', 'mf.allocation_id', '=', 'ma.id')
                    ->join('mushroom_quota_districts as mq', 'ma.quota_id', '=', 'mq.id')
                    ->where('mq.year', $year);
        }

        $totals = [
            'total_enterprise_count' => (clone $followQ)
                ->where('mf.enterprise_member', 1)
                ->whereNotNull('mf.enterprise_name')
                ->distinct()->count('mf.enterprise_name'),
            'total_revenue'          => (float) (clone $followQ)->sum('mf.revenue'),
            'enterprise_revenue'     => (float) (clone $followQ)
                ->where('mf.enterprise_member', 1)
                ->sum('mf.revenue'),
        ];

        return compact('totals', 'byEnterprise');
    }
}
