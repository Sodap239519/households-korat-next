<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\SellerApplication;
use App\Models\SellerGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * หลังบ้าน — จัดการกลุ่มผู้ขาย (admin/superadmin เท่านั้น)
 * รวมตั้งค่าอำเภอที่ดูแล + ปลายทางแจ้งเตือน LINE + สมาชิกกลุ่ม
 */
class SellerGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $query = SellerGroup::withCount(['products', 'members', 'orders']);

        // เจ้าหน้าที่กลุ่มเห็นเฉพาะกลุ่มตัวเอง (ไว้ใช้เป็น dropdown/ข้อมูลอ้างอิง)
        if ($scope = $user->sellerGroupScope()) {
            $query->where('id', $scope);
        }

        return response()->json(
            $query->with(['members:id,name,email,seller_group_id'])
                  ->orderBy('name')->get()
        );
    }

    public function show(Request $request, SellerGroup $sellerGroup): JsonResponse
    {
        abort_unless($request->user()->canManageSellerGroups() || $request->user()->canActInGroup($sellerGroup->id), 403);
        return response()->json($sellerGroup->load('members:id,name,email,role,seller_group_id'));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->canManageSellerGroups(), 403, 'เฉพาะผู้ดูแลระบบที่จัดการกลุ่มผู้ขายได้');

        $validated = $this->validateData($request);
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        $group = SellerGroup::create($validated);
        return response()->json($group, 201);
    }

    public function update(Request $request, SellerGroup $sellerGroup): JsonResponse
    {
        // admin แก้ได้ทุกอย่าง; เจ้าหน้าที่กลุ่มแก้ได้เฉพาะข้อมูลติดต่อ/LINE ของกลุ่มตัวเอง
        $user = $request->user();
        $isOwnGroupStaff = !$user->isAdmin() && $user->canActInGroup($sellerGroup->id);
        abort_unless($user->canManageSellerGroups() || $isOwnGroupStaff, 403, 'ไม่มีสิทธิ์แก้ไขกลุ่มนี้');

        if ($isOwnGroupStaff) {
            // จำกัดเฉพาะ field ที่กลุ่มแก้เองได้
            $validated = $request->validate([
                'description'         => ['nullable', 'string'],
                'contact_phone'       => ['nullable', 'string', 'max:30'],
                'contact_address'     => ['nullable', 'string', 'max:255'],
                'bank_name'           => ['nullable', 'string', 'max:255'],
                'bank_account_no'     => ['nullable', 'string', 'max:30'],
                'bank_account_name'   => ['nullable', 'string', 'max:255'],
                'promptpay_id'        => ['nullable', 'string', 'max:30'],
                'line_target_id'      => ['nullable', 'string', 'max:255'],
                'line_notify_enabled' => ['boolean'],
            ]);
        } else {
            $validated = $this->validateData($request, $sellerGroup);
        }

        $sellerGroup->update($validated);
        return response()->json($sellerGroup);
    }

    public function destroy(Request $request, SellerGroup $sellerGroup): JsonResponse
    {
        abort_unless($request->user()->canManageSellerGroups(), 403, 'เฉพาะผู้ดูแลระบบที่ลบกลุ่มได้');
        abort_if($sellerGroup->products()->exists(), 422, 'ลบไม่ได้ — ยังมีสินค้าในกลุ่มนี้');
        abort_if($sellerGroup->orders()->exists(), 422, 'ลบไม่ได้ — ยังมีคำสั่งซื้อในกลุ่มนี้');

        $sellerGroup->members()->update(['seller_group_id' => null]);
        $sellerGroup->delete();
        return response()->json(['message' => 'ลบกลุ่มแล้ว']);
    }

    /** อัปโหลดโลโก้/รูปกลุ่ม (admin หรือเจ้าหน้าที่กลุ่มตัวเอง) */
    public function uploadLogo(Request $request, SellerGroup $sellerGroup): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canManageSellerGroups() || $user->canActInGroup($sellerGroup->id), 403, 'ไม่มีสิทธิ์');

        $request->validate(['logo' => ['required', 'image', 'max:2048']]); // 2MB

        if ($sellerGroup->logo_path) {
            Storage::disk('public')->delete($sellerGroup->logo_path);
        }

        $path = $request->file('logo')->store("seller-groups/{$sellerGroup->id}", 'public');
        $sellerGroup->update(['logo_path' => $path]);

        // ซิงค์รูป avatar ของ user ที่เป็นเจ้าของร้าน เพื่อให้หน้าร้านแสดงรูปล่าสุด
        if ($user->seller_group_id === $sellerGroup->id) {
            $user->update(['avatar_path' => $path]);
        }

        return response()->json(['logo_url' => Storage::disk('public')->url($path)]);
    }

    /** ลบโลโก้กลุ่ม */
    public function deleteLogo(Request $request, SellerGroup $sellerGroup): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canManageSellerGroups() || $user->canActInGroup($sellerGroup->id), 403, 'ไม่มีสิทธิ์');

        if ($sellerGroup->logo_path) {
            Storage::disk('public')->delete($sellerGroup->logo_path);
            $sellerGroup->update(['logo_path' => null]);
        }

        return response()->json(['message' => 'ลบโลโก้แล้ว']);
    }

    /** GET /market/my-group — ข้อมูลกลุ่มของตัวเอง + ข้อมูลจาก seller application */
    public function myGroup(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->seller_group_id, 403, 'คุณยังไม่ได้สังกัดกลุ่มผู้ขาย');

        $group = SellerGroup::find($user->seller_group_id);
        abort_unless($group, 404, 'ไม่พบข้อมูลกลุ่ม');

        // ค้นหา seller application ที่เกี่ยวข้องกับ user หรือกลุ่มนี้
        $application = SellerApplication::where(function ($q) use ($user, $group) {
            $q->where('applicant_email', $user->email)
              ->orWhere('applicant_user_id', $user->id)
              ->orWhere('created_user_id', $user->id)
              ->orWhere(fn ($q2) => $q2
                  ->whereIn('requested_group_id', [$group->id])
                  ->whereIn('assigned_group_id', [$group->id])
              );
        })->latest()->first()
        // fallback: หา application ที่ requested_group_id ตรงกัน
        ?? SellerApplication::where('requested_group_id', $group->id)->latest()->first();

        $appData = null;
        if ($application) {
            $cats = $application->business_categories;
            if (is_string($cats)) $cats = json_decode($cats, true);
            $appData = [
                'business_name'        => $application->business_name,
                'business_type'        => $application->business_type,
                'business_categories'  => $cats ?? [],
                'products_description' => $application->products_description,
                'logo_url'             => $application->logo_path
                    ? Storage::disk('public')->url($application->logo_path)
                    : null,
                'applicant_name'  => $application->applicant_name,
                'district'         => $application->district,
                'sub_district'     => $application->sub_district,
                'business_address' => $application->business_address,
            ];
        }

        return response()->json([
            'group'       => $group,
            'application' => $appData,
            // รูปโปรไฟล์ของร้าน (avatar ของ user เอง — แยกจากโลโก้โซน)
            'avatar_url'  => $user->avatar_path
                ? Storage::disk('public')->url($user->avatar_path)
                : null,
        ]);
    }

    /** POST /market/my-avatar — อัปโหลดรูปโปรไฟล์ร้านของตัวเอง (แก้เฉพาะ avatar ของ user ไม่แตะโลโก้โซน) */
    public function uploadMyAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->seller_group_id, 403, 'คุณยังไม่ได้สังกัดกลุ่มผู้ขาย');

        $request->validate(['logo' => ['required', 'image', 'max:2048']]);

        // ลบรูปเก่าเฉพาะที่เป็นของ user เอง (ไม่ลบถ้า path ตรงกับโลโก้โซน — อาจแชร์กัน)
        $group = SellerGroup::find($user->seller_group_id);
        if ($user->avatar_path && $user->avatar_path !== $group?->logo_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('logo')->store("seller-avatars/{$user->id}", 'public');
        $user->update(['avatar_path' => $path]);

        return response()->json(['avatar_url' => Storage::disk('public')->url($path)]);
    }

    /** DELETE /market/my-avatar — ลบรูปโปรไฟล์ร้านของตัวเอง */
    public function deleteMyAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->seller_group_id, 403, 'คุณยังไม่ได้สังกัดกลุ่มผู้ขาย');

        $group = SellerGroup::find($user->seller_group_id);
        if ($user->avatar_path && $user->avatar_path !== $group?->logo_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
        $user->update(['avatar_path' => null]);

        return response()->json(['message' => 'ลบรูปโปรไฟล์แล้ว']);
    }

    /** PUT /market/seller-groups/my-group — เจ้าของร้านแก้ไขข้อมูลร้านและใบสมัครของตัวเอง */
    public function updateMyGroup(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->seller_group_id, 403, 'คุณยังไม่ได้สังกัดกลุ่มผู้ขาย');

        $group = SellerGroup::find($user->seller_group_id);
        abort_unless($group, 404, 'ไม่พบข้อมูลกลุ่ม');

        $validated = $request->validate([
            // กลุ่ม
            'description'          => ['nullable', 'string'],
            'contact_phone'        => ['nullable', 'string', 'max:30'],
            'contact_address'      => ['nullable', 'string', 'max:255'],
            'bank_name'            => ['nullable', 'string', 'max:255'],
            'bank_account_no'      => ['nullable', 'string', 'max:30'],
            'bank_account_name'    => ['nullable', 'string', 'max:255'],
            'promptpay_id'         => ['nullable', 'string', 'max:30'],
            // ใบสมัคร
            'business_name'        => ['nullable', 'string', 'max:255'],
            'products_description' => ['nullable', 'string'],
            'district'             => ['nullable', 'string', 'max:100'],
            'sub_district'         => ['nullable', 'string', 'max:100'],
            'business_address'     => ['nullable', 'string', 'max:255'],
        ]);

        $group->update(array_intersect_key($validated, array_flip([
            'description', 'contact_phone', 'contact_address',
            'bank_name', 'bank_account_no', 'bank_account_name', 'promptpay_id',
        ])));

        $application = SellerApplication::where(function ($q) use ($user, $group) {
            $q->where('applicant_email', $user->email)
              ->orWhere('applicant_user_id', $user->id)
              ->orWhere('created_user_id', $user->id)
              ->orWhere(fn ($q2) => $q2
                  ->whereIn('requested_group_id', [$group->id])
                  ->whereIn('assigned_group_id', [$group->id])
              );
        })->latest()->first()
        ?? SellerApplication::where('requested_group_id', $group->id)->latest()->first();

        if ($application) {
            $appFields = array_filter(
                array_intersect_key($validated, array_flip([
                    'business_name', 'products_description', 'district', 'sub_district', 'business_address',
                ])),
                fn ($v) => $v !== null
            );
            if ($appFields) {
                $application->update($appFields);
            }
        }

        return response()->json(['message' => 'บันทึกสำเร็จ', 'group' => $group]);
    }

    /** กำหนด/ปลดสมาชิกของกลุ่ม (admin เท่านั้น) */
    public function setMembers(Request $request, SellerGroup $sellerGroup): JsonResponse
    {
        abort_unless($request->user()->canManageSellerGroups(), 403, 'เฉพาะผู้ดูแลระบบที่จัดการสมาชิกได้');

        $validated = $request->validate([
            'user_ids'   => ['present', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        // ปลดสมาชิกเดิมที่ไม่อยู่ในรายการใหม่ แล้วผูกสมาชิกใหม่ (เฉพาะ user ที่เป็นเจ้าหน้าที่)
        $ids = $validated['user_ids'];
        User::where('seller_group_id', $sellerGroup->id)->whereNotIn('id', $ids)
            ->update(['seller_group_id' => null]);
        User::whereIn('id', $ids)->whereIn('role', User::ROLES_ALL)
            ->update(['seller_group_id' => $sellerGroup->id]);

        return response()->json($sellerGroup->load('members:id,name,email,role,seller_group_id'));
    }

    private function validateData(Request $request, ?SellerGroup $group = null): array
    {
        $required = $group ? 'sometimes' : 'required';

        return $request->validate([
            'name'                => [$required, 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'contact_phone'       => ['nullable', 'string', 'max:30'],
            'contact_address'     => ['nullable', 'string', 'max:255'],
            'bank_name'           => ['nullable', 'string', 'max:255'],
            'bank_account_no'     => ['nullable', 'string', 'max:30'],
            'bank_account_name'   => ['nullable', 'string', 'max:255'],
            'promptpay_id'        => ['nullable', 'string', 'max:30'],
            'districts'           => ['nullable', 'array'],
            'districts.*'         => ['string', 'max:100'],
            'lat'                 => ['nullable', 'numeric', 'between:-90,90'],
            'lng'                 => ['nullable', 'numeric', 'between:-180,180'],
            'map_label'           => ['nullable', 'string', 'max:100'],
            'line_target_id'      => ['nullable', 'string', 'max:255'],
            'line_notify_enabled' => ['boolean'],
            'is_active'           => ['boolean'],
        ]);
    }

    /** POST /market/seller-groups/{id}/ban — แบนร้านค้าถาวร */
    public function ban(Request $request, SellerGroup $sellerGroup): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'เฉพาะ superadmin เท่านั้น');
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $sellerGroup->update([
            'shop_status' => SellerGroup::SHOP_STATUS_BANNED,
            'ban_reason'  => $data['reason'],
            'banned_by'   => $request->user()->id,
            'banned_at'   => now(),
            'suspended_until' => null,
        ]);
        return response()->json(['message' => 'แบนร้านค้าแล้ว', 'shop_status' => $sellerGroup->shop_status]);
    }

    /** POST /market/seller-groups/{id}/suspend — ระงับชั่วคราว */
    public function suspend(Request $request, SellerGroup $sellerGroup): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin/superadmin เท่านั้น');
        $data = $request->validate([
            'reason'          => ['required', 'string', 'max:500'],
            'suspended_until' => ['required', 'date', 'after:now'],
        ]);
        $sellerGroup->update([
            'shop_status'     => SellerGroup::SHOP_STATUS_SUSPENDED,
            'ban_reason'      => $data['reason'],
            'suspended_until' => $data['suspended_until'],
            'banned_by'       => $request->user()->id,
            'banned_at'       => now(),
        ]);
        return response()->json(['message' => 'ระงับร้านค้าชั่วคราวแล้ว', 'shop_status' => $sellerGroup->shop_status]);
    }

    /** POST /market/seller-groups/{id}/restore — คืนสถานะปกติ */
    public function restore(Request $request, SellerGroup $sellerGroup): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะ admin/superadmin เท่านั้น');
        $sellerGroup->update([
            'shop_status'     => SellerGroup::SHOP_STATUS_ACTIVE,
            'ban_reason'      => null,
            'suspended_until' => null,
            'banned_by'       => null,
            'banned_at'       => null,
        ]);
        return response()->json(['message' => 'คืนสถานะร้านค้าแล้ว', 'shop_status' => $sellerGroup->shop_status]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = ProductController::thaiSlug($name) ?: 'group';
        $slug = $base;
        $i = 1;
        while (SellerGroup::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
