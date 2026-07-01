<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\SellerApplication;
use App\Models\SellerGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Backoffice — admin/superadmin พิจารณาคำขอสมัครขาย
 *
 * Flow:
 *  pending  ─[admin approve]─→ admin_approved ─[superadmin approve]─→ approved
 *           ─[admin reject]──→ rejected
 *           ─[1 hr no action]→ escalated ─[superadmin approve/reject]
 */
class SellerApplicationController extends Controller
{
    /** รายการคำขอ — admin เห็น pending/admin_approved, superadmin เห็นทุกสถานะ */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403, 'เฉพาะ admin/superadmin เท่านั้น');

        $query = SellerApplication::with(['requestedGroup:id,name', 'admin:id,name', 'superadmin:id,name'])
            ->orderByDesc('created_at');

        if (!$user->isSuperAdmin()) {
            // admin ธรรมดา: เห็นเฉพาะ pending + admin_approved (ที่ตัวเองดูแล)
            $query->whereIn('status', [
                SellerApplication::STATUS_PENDING,
                SellerApplication::STATUS_ADMIN_APPROVED,
            ]);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(20));
    }

    /** จำนวนคำขอแยกตามสถานะ (ใช้แสดง badge บน tab) */
    public function counts(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403, 'เฉพาะ admin/superadmin เท่านั้น');

        $rows = SellerApplication::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $all = [
            'pending'            => 0,
            'admin_approved'     => 0,
            'escalated'          => 0,
            'revision_requested' => 0,
            'approved'           => 0,
            'rejected'           => 0,
        ];

        foreach ($rows as $status => $count) {
            if (isset($all[$status])) $all[$status] = (int) $count;
        }

        if (!$user->isSuperAdmin()) {
            return response()->json([
                'pending'        => $all['pending'],
                'admin_approved' => $all['admin_approved'],
            ]);
        }

        return response()->json($all);
    }

    /** รายละเอียดคำขอ */
    public function show(Request $request, SellerApplication $sellerApplication): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403, 'เฉพาะ admin/superadmin เท่านั้น');

        // admin ธรรมดาดูได้เฉพาะ pending/admin_approved
        if (!$user->isSuperAdmin()) {
            abort_unless(
                in_array($sellerApplication->status, [SellerApplication::STATUS_PENDING, SellerApplication::STATUS_ADMIN_APPROVED]),
                403, 'ไม่มีสิทธิ์ดูคำขอนี้'
            );
        }

        $sellerApplication->load(['requestedGroup:id,name', 'admin:id,name', 'superadmin:id,name', 'assignedGroup:id,name', 'createdUser:id,name,email']);

        return response()->json($sellerApplication);
    }

    /**
     * Admin พิจารณาขั้น 1: approve หรือ reject
     * POST /market/seller-applications/{id}/admin-review
     * body: { action: 'approve'|'reject', note: string }
     */
    public function adminReview(Request $request, SellerApplication $sellerApplication): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin(), 403, 'เฉพาะ admin เท่านั้น');
        // superadmin ไม่ใช้ route นี้ (superadmin ข้ามไป finalReview)
        abort_unless(!$user->isSuperAdmin(), 403, 'superadmin ใช้ /final-review แทน');
        abort_unless($sellerApplication->status === SellerApplication::STATUS_PENDING, 422, 'คำขอนี้ไม่อยู่ในสถานะรอพิจารณา');

        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'note'   => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = $data['action'] === 'approve'
            ? SellerApplication::STATUS_ADMIN_APPROVED
            : SellerApplication::STATUS_REJECTED;

        $sellerApplication->update([
            'status'           => $newStatus,
            'admin_id'         => $user->id,
            'admin_note'       => $data['note'] ?? null,
            'admin_reviewed_at'=> now(),
        ]);

        return response()->json([
            'message' => $data['action'] === 'approve' ? 'ส่งต่อ superadmin แล้ว' : 'ปฏิเสธคำขอแล้ว',
            'status'  => $sellerApplication->status,
        ]);
    }

    /**
     * Super Admin พิจารณาขั้นสุดท้าย
     * POST /market/seller-applications/{id}/final-review
     * body: { action: 'approve'|'reject', note, assigned_group_id?, new_group_name? }
     *
     * กรณี approve:
     *  - ถ้า assigned_group_id → กำหนดกลุ่มที่มีอยู่
     *  - ถ้า new_group_name   → สร้างกลุ่มใหม่ (ต้องการ slug ด้วย หรือ auto-generate)
     *  - สร้าง user account role=staff พร้อม seller_group_id
     */
    public function finalReview(Request $request, SellerApplication $sellerApplication): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin(), 403, 'เฉพาะ superadmin เท่านั้น');
        abort_unless($sellerApplication->needsSuperAdminReview(), 422, 'คำขอนี้ยังไม่ถึงขั้นตอน superadmin');

        $data = $request->validate([
            'action'            => ['required', 'in:approve,reject'],
            'note'              => ['nullable', 'string', 'max:500'],
            'assigned_group_id' => ['nullable', 'integer', 'exists:seller_groups,id'],
            'new_group_name'    => ['nullable', 'string', 'max:100'],
            'temp_password'     => ['nullable', 'string', 'min:8', 'max:100'],
        ]);

        if ($data['action'] === 'reject') {
            $sellerApplication->update([
                'status'                  => SellerApplication::STATUS_REJECTED,
                'superadmin_id'           => $user->id,
                'superadmin_note'         => $data['note'] ?? null,
                'superadmin_reviewed_at'  => now(),
            ]);
            return response()->json(['message' => 'ปฏิเสธคำขอแล้ว', 'status' => SellerApplication::STATUS_REJECTED]);
        }

        // === Approve (fall-through) ===
        return DB::transaction(function () use ($sellerApplication, $data, $user) {
            // 1. กำหนดกลุ่ม
            $group = null;
            if (!empty($data['assigned_group_id'])) {
                $group = SellerGroup::find($data['assigned_group_id']);
            } elseif (!empty($data['new_group_name'])) {
                $group = SellerGroup::create([
                    'name'      => $data['new_group_name'],
                    'slug'      => Str::slug($data['new_group_name']) . '-' . Str::random(4),
                    'is_active' => true,
                    'districts' => $sellerApplication->district ? [$sellerApplication->district] : [],
                ]);
            } elseif ($sellerApplication->requested_group_id) {
                $group = SellerGroup::find($sellerApplication->requested_group_id);
            }

            // 2. สร้าง user account ให้ผู้สมัคร
            $tempPassword = $data['temp_password'] ?? Str::random(10);
            $newUser = User::create([
                'name'            => $sellerApplication->applicant_name,
                'email'           => $sellerApplication->applicant_email
                    ?? Str::slug($sellerApplication->business_name) . '@seller.local',
                'password'        => Hash::make($tempPassword),
                'role'            => User::ROLE_STAFF,
                'seller_group_id' => $group?->id,
                'is_approved'     => true,
                'approved_at'     => now(),
                'approved_by'     => $user->id,
            ]);

            // 3. อัปเดตคำขอ
            $sellerApplication->update([
                'status'                  => SellerApplication::STATUS_APPROVED,
                'superadmin_id'           => $user->id,
                'superadmin_note'         => $data['note'] ?? null,
                'superadmin_reviewed_at'  => now(),
                'assigned_group_id'       => $group?->id,
                'created_user_id'         => $newUser->id,
            ]);

            // เก็บ temp password ไว้ 7 วัน (ยังไม่เริ่มนับ 30 นาที — เริ่มเมื่อผู้สมัครกดเปิดดูเอง)
            Cache::put('seller_temp_pw_' . $sellerApplication->token, $tempPassword, now()->addDays(7));

            return response()->json([
                'message'       => 'อนุมัติสำเร็จ สร้างบัญชีผู้ขายแล้ว',
                'status'        => SellerApplication::STATUS_APPROVED,
                'created_user'  => [
                    'id'    => $newUser->id,
                    'name'  => $newUser->name,
                    'email' => $newUser->email,
                ],
                'temp_password' => $tempPassword,
                'group'         => $group ? ['id' => $group->id, 'name' => $group->name] : null,
            ]);
        });
    }

    /**
     * Admin หรือ Superadmin ตีกลับให้ผู้สมัครแก้ไข
     * POST /market/seller-applications/{id}/request-revision
     * body: { note: string }
     */
    public function requestRevision(Request $request, SellerApplication $sellerApplication): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isSuperAdmin(), 403, 'เฉพาะ admin/superadmin เท่านั้น');
        abort_unless(
            in_array($sellerApplication->status, [
                SellerApplication::STATUS_PENDING,
                SellerApplication::STATUS_ADMIN_APPROVED,
                SellerApplication::STATUS_ESCALATED,
                SellerApplication::STATUS_REVISION_REQUESTED,
            ]),
            422, 'ไม่สามารถตีกลับในสถานะนี้'
        );

        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        $sellerApplication->update([
            'status'        => SellerApplication::STATUS_REVISION_REQUESTED,
            'revision_note' => $data['note'],
        ]);

        return response()->json([
            'message' => 'ตีกลับให้ผู้สมัครแก้ไขแล้ว',
            'status'  => $sellerApplication->status,
        ]);
    }
}
