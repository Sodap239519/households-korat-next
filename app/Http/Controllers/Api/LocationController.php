<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function districts(): JsonResponse
    {
        $districts = DB::table('households')
            ->whereNull('deleted_at')
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');

        return response()->json($districts);
    }

    public function subDistricts(Request $request): JsonResponse
    {
        $query = DB::table('households')
            ->whereNull('deleted_at')
            ->whereNotNull('sub_district')
            ->where('sub_district', '!=', '');

        if ($d = $request->input('district')) $query->where('district', $d);

        return response()->json(
            $query->distinct()->orderBy('sub_district')->pluck('sub_district')
        );
    }

    public function villages(Request $request): JsonResponse
    {
        $query = DB::table('households')
            ->whereNull('deleted_at')
            ->whereNotNull('village')
            ->where('village', '!=', '');

        if ($d = $request->input('district'))    $query->where('district', $d);
        if ($s = $request->input('sub_district')) $query->where('sub_district', $s);

        return response()->json(
            $query->distinct()->orderBy('village')->pluck('village')
        );
    }

    public function provinces(): JsonResponse
    {
        $provinces = DB::table('households')
            ->whereNull('deleted_at')
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->distinct()
            ->orderBy('province')
            ->pluck('province');

        return response()->json($provinces);
    }
}
