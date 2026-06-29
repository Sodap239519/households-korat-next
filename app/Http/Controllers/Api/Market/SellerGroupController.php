<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\SellerGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return response()->json($query->orderBy('name')->get());
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
