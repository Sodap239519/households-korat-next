<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\SellerApplication;
use App\Models\SellerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Public — ผู้สนใจสมัครเป็นผู้ขาย (ไม่ต้อง login)
 */
class SellerApplicationController extends Controller
{
    /** รายชื่อกลุ่มผู้ขาย (เพื่อให้เลือกในฟอร์มสมัคร) */
    public function groups(): JsonResponse
    {
        $groups = SellerGroup::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'districts']);

        return response()->json($groups);
    }

    /** ส่งคำขอสมัครขาย */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'applicant_name'      => ['required', 'string', 'max:100'],
            'applicant_phone'     => ['required', 'string', 'max:30'],
            'applicant_email'     => ['required', 'email', 'max:150'],
            'applicant_id_card'   => ['nullable', 'string', 'max:20'],
            'business_name'          => ['required', 'string', 'max:150'],
            'business_type'          => ['nullable', 'string', 'max:100'],
            'business_categories'    => ['nullable', 'array'],
            'business_categories.*'  => ['string', 'max:60'],
            'district'               => ['nullable', 'string', 'max:100'],
            'sub_district'           => ['nullable', 'string', 'max:100'],
            'business_address'       => ['nullable', 'string', 'max:255'],
            'lat'                    => ['nullable', 'numeric', 'between:-90,90'],
            'lng'                    => ['nullable', 'numeric', 'between:-180,180'],
            'products_description'=> ['nullable', 'string', 'max:2000'],
            'note'                => ['nullable', 'string', 'max:1000'],
            'requested_group_id'  => ['nullable', 'integer', 'exists:seller_groups,id'],
            'logo'                => ['nullable', 'file', 'max:2048'],
            'documents'           => ['nullable', 'array', 'max:5'],
            'documents.*'         => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [], [
            'applicant_name'  => 'ชื่อ-นามสกุล',
            'applicant_phone' => 'เบอร์โทร',
            'business_name'   => 'ชื่อร้าน/กิจการ',
        ]);

        // B: อำเภอ → โซนอัตโนมัติ (เผื่อผู้สมัครไม่ได้เลือกกลุ่มเอง)
        if (empty($data['requested_group_id']) && !empty($data['district'])) {
            $group = SellerGroup::where('is_active', true)->get()
                ->first(fn ($g) => in_array($data['district'], $g->districts ?? [], true));
            if ($group) $data['requested_group_id'] = $group->id;
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('seller-applications/logos', 'public');
        }

        $docPaths = [];
        foreach ($request->file('documents', []) as $file) {
            $docPaths[] = $file->store('seller-applications', 'public');
        }

        $app = SellerApplication::create([
            ...$data,
            'applicant_user_id' => Auth::id() ?? auth('web')->id(),
            'logo_path'         => $logoPath,
            'documents'         => $docPaths ?: null,
        ]);

        return response()->json([
            'message' => 'ส่งคำขอสมัครสำเร็จ เราจะติดต่อกลับภายใน 1-3 วันทำการ',
            'token'   => $app->token,
        ], 201);
    }

    /** อัปเดตคำขอที่ถูกตีกลับ (ต้อง login) */
    public function update(Request $request, string $token): JsonResponse
    {
        $user = Auth::user();
        $app  = SellerApplication::where('token', $token)
            ->where(function ($q) use ($user) {
                $q->where('applicant_user_id', $user->id);
                if ($user->email) $q->orWhere('applicant_email', $user->email);
            })
            ->firstOrFail();

        abort_unless($app->status === SellerApplication::STATUS_REVISION_REQUESTED, 422, 'ไม่สามารถแก้ไขได้ในสถานะนี้');

        $data = $request->validate([
            'applicant_name'      => ['required', 'string', 'max:100'],
            'applicant_phone'     => ['required', 'string', 'max:30'],
            'applicant_email'     => ['required', 'email', 'max:150'],
            'applicant_id_card'   => ['nullable', 'string', 'max:20'],
            'business_name'          => ['required', 'string', 'max:150'],
            'business_type'          => ['nullable', 'string', 'max:100'],
            'business_categories'    => ['nullable', 'array'],
            'business_categories.*'  => ['string', 'max:60'],
            'district'               => ['nullable', 'string', 'max:100'],
            'sub_district'           => ['nullable', 'string', 'max:100'],
            'business_address'       => ['nullable', 'string', 'max:255'],
            'lat'                    => ['nullable', 'numeric', 'between:-90,90'],
            'lng'                    => ['nullable', 'numeric', 'between:-180,180'],
            'products_description'=> ['nullable', 'string', 'max:2000'],
            'note'                => ['nullable', 'string', 'max:1000'],
            'requested_group_id'  => ['nullable', 'integer', 'exists:seller_groups,id'],
            'logo'                => ['nullable', 'file', 'max:2048'],
            'documents'           => ['nullable', 'array', 'max:5'],
            'documents.*'         => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $logoPath = $app->logo_path;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('seller-applications/logos', 'public');
        }

        $docPaths = $app->documents ?? [];
        foreach ($request->file('documents', []) as $file) {
            $docPaths[] = $file->store('seller-applications', 'public');
        }

        $app->update([
            ...$data,
            'logo_path'     => $logoPath,
            'documents'     => $docPaths ?: null,
            'status'        => SellerApplication::STATUS_PENDING,
            'revision_note' => null,
        ]);

        return response()->json(['message' => 'แก้ไขและส่งคำขอใหม่แล้ว', 'token' => $app->token]);
    }

    /** ดึงข้อมูลฟอร์มเพื่อแก้ไข — เฉพาะเจ้าของและสถานะ revision_requested */
    public function edit(string $token): JsonResponse
    {
        $user = Auth::user();
        $app  = SellerApplication::where('token', $token)
            ->where(function ($q) use ($user) {
                $q->where('applicant_user_id', $user->id);
                if ($user->email) $q->orWhere('applicant_email', $user->email);
            })
            ->firstOrFail();

        abort_unless($app->status === SellerApplication::STATUS_REVISION_REQUESTED, 403);

        return response()->json($app);
    }

    /** คำขอของฉัน (ต้อง login) */
    public function mine(): JsonResponse
    {
        $user = Auth::user();
        $apps = SellerApplication::where(function ($q) use ($user) {
            $q->where('applicant_user_id', $user->id);
            if ($user->email) {
                $q->orWhere('applicant_email', $user->email);
            }
        })
            ->with('requestedGroup:id,name')
            ->orderByDesc('created_at')
            ->get(['id', 'token', 'business_name', 'district', 'status', 'created_at', 'requested_group_id']);

        return response()->json($apps->map(fn ($a) => [
            'id'              => $a->id,
            'token'           => $a->token,
            'business_name'   => $a->business_name,
            'district'        => $a->district,
            'status'          => $a->status,
            'status_label'    => $a->status_label,
            'submitted_at'    => $a->created_at?->toIso8601String(),
            'requested_group' => $a->requestedGroup ? ['name' => $a->requestedGroup->name] : null,
        ]));
    }

    /** ติดตามสถานะด้วย token */
    public function status(string $token): JsonResponse
    {
        $app = SellerApplication::where('token', $token)->firstOrFail();

        $createdUserEmail  = null;
        $tempPassword      = null;
        $passwordExpiresAt = null;
        $passwordAvailable = false;
        $tempPasswordHint  = null;

        if ($app->status === 'approved' && $app->created_user_id) {
            $createdUserEmail = \App\Models\User::find($app->created_user_id)?->email;

            // ตรวจสถานะรหัสผ่าน: ถูกเผยแล้ว (30 นาที) หรือยังรอการกดเปิด
            $active = Cache::get('seller_temp_pw_active_' . $app->token);
            if ($active) {
                $tempPassword      = $active['password'];
                $passwordExpiresAt = $active['expires_at'];
            } else {
                $passwordAvailable = Cache::has('seller_temp_pw_' . $app->token);
            }
            // hint (3 ตัวท้ายของรหัสผ่านชั่วคราว) — ใช้แสดงเมื่อหมดอายุแล้ว
            $tempPasswordHint = Cache::get('seller_temp_pw_hint_' . $app->token);
        }

        return response()->json([
            'status'              => $app->status,
            'status_label'        => $app->status_label,
            'token'               => $app->token,
            'business_name'       => $app->business_name,
            'applicant_name'      => $app->applicant_name,
            'applicant_email'     => $app->applicant_email,
            'submitted_at'        => $app->created_at?->toIso8601String(),
            'admin_note'          => in_array($app->status, ['rejected']) ? $app->admin_note : null,
            'superadmin_note'     => in_array($app->status, ['rejected', 'approved']) ? $app->superadmin_note : null,
            'revision_note'       => $app->status === 'revision_requested' ? $app->revision_note : null,
            'created_user_email'  => $createdUserEmail,
            'password_available'  => $passwordAvailable,
            'temp_password'       => $tempPassword,
            'password_expires_at' => $passwordExpiresAt,
            'temp_password_hint'  => $tempPasswordHint,
        ]);
    }

    /**
     * ผู้สมัครกดเปิดดูรหัสผ่านชั่วคราว — เริ่มนับ 30 นาที ณ จุดนี้
     * POST /seller/apply/{token}/reveal-password
     */
    public function revealPassword(string $token): JsonResponse
    {
        $app = SellerApplication::where('token', $token)
            ->where('status', SellerApplication::STATUS_APPROVED)
            ->firstOrFail();

        // ถ้ายังอยู่ในช่วง 30 นาที ส่งกลับค่าเดิม
        $active = Cache::get('seller_temp_pw_active_' . $token);
        if ($active) {
            return response()->json([
                'temp_password'       => $active['password'],
                'password_expires_at' => $active['expires_at'],
            ]);
        }

        // ดึงจาก long-term cache (ยังไม่เคยถูกเปิด)
        $plainPw = Cache::get('seller_temp_pw_' . $token);
        if (!$plainPw) {
            return response()->json(['message' => 'รหัสผ่านหมดอายุแล้ว กรุณาติดต่อทีมงาน'], 410);
        }

        $expiresAt = now()->addMinutes(30);
        Cache::put('seller_temp_pw_active_' . $token, [
            'password'   => $plainPw,
            'expires_at' => $expiresAt->toIso8601String(),
        ], $expiresAt);
        Cache::forget('seller_temp_pw_' . $token);

        // เก็บ hint (3 ตัวท้าย) ไว้นานๆ เผื่อรหัสผ่านหมดอายุแล้ว ยังดู hint ได้
        Cache::put('seller_temp_pw_hint_' . $token, substr($plainPw, -3), now()->addDays(365));

        return response()->json([
            'temp_password'       => $plainPw,
            'password_expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    /**
     * POST /seller/apply/{token}/reset-password
     * ตั้งรหัสผ่านใหม่ด้วยตัวเอง โดยใช้ token ของใบสมัครเป็นหลักฐาน (ไม่ต้องพึ่งอีเมล)
     */
    public function resetPassword(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [], ['password' => 'รหัสผ่านใหม่']);

        $app = SellerApplication::where('token', $token)
            ->where('status', SellerApplication::STATUS_APPROVED)
            ->firstOrFail();

        abort_unless($app->created_user_id, 422, 'ยังไม่มีบัญชีผู้ใช้สำหรับใบสมัครนี้');

        $user = \App\Models\User::find($app->created_user_id);
        abort_unless($user, 404, 'ไม่พบบัญชีผู้ใช้');

        $user->update([
            'password'             => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        // เคลียร์รหัสชั่วคราวทั้งหมด (ไม่จำเป็นแล้ว)
        Cache::forget('seller_temp_pw_' . $token);
        Cache::forget('seller_temp_pw_active_' . $token);
        Cache::forget('seller_temp_pw_hint_' . $token);

        return response()->json(['message' => 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว เข้าสู่ระบบด้วยรหัสใหม่ได้เลย']);
    }
}
