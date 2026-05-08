<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    /**
     * Latest "data was modified" timestamp — optionally scoped to a single section.
     * Used by the layout header to show "ข้อมูลอัพเดตล่าสุดวันที่ X เวลา Y" relevant
     * to the page the user is currently viewing.
     *
     * Supported ?scope= values:
     *   households   — รายการครัวเรือน + ตัวกรอง/การติดตาม
     *   quotas       — โควต้าอำเภอ
     *   allocations  — การจัดสรร
     *   followups    — การติดตาม / การตลาด
     *   users        — บัญชีผู้ใช้ (admin)
     *   all (default)— max ของทุกตาราง
     */
    public function lastUpdated(Request $request): JsonResponse
    {
        $scope = $request->input('scope', 'all');

        $sources = [
            'households'  => fn () => DB::table('households')->max('updated_at'),
            'quotas'      => fn () => DB::table('mushroom_quota_districts')->max('updated_at'),
            'allocations' => fn () => DB::table('mushroom_allocations')->max('updated_at'),
            'followups'   => fn () => DB::table('mushroom_followups')->max('updated_at'),
            'users'       => fn () => DB::table('users')->max('updated_at'),
        ];

        if ($scope !== 'all' && isset($sources[$scope])) {
            $latest = $sources[$scope]();
        } else {
            $values = array_filter(array_map(fn ($fn) => $fn(), $sources));
            $latest = $values ? max($values) : null;
            $scope = 'all';
        }

        return response()->json([
            'scope' => $scope,
            'at'    => $latest,                                                   // raw "Y-m-d H:i:s"
            'iso'   => $latest ? \Carbon\Carbon::parse($latest)->toIso8601String() : null,
        ]);
    }
}
