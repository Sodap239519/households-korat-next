<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\SellerShippingOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * หลังบ้าน — จัดการบริการจัดส่งแบบ global (ใช้ร่วมกันทุกกลุ่มผู้ขาย)
 * อ่าน: ทุก staff; เขียน/ลบ: admin/superadmin เท่านั้น
 */
class SellerShippingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');
        return response()->json(
            SellerShippingOption::whereNull('seller_group_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะผู้ดูแลระบบเท่านั้นที่จัดการบริการจัดส่งได้');

        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:100'],
            'carrier'                 => ['nullable', 'string', 'max:60'],
            'fee'                     => ['required', 'numeric', 'min:0', 'max:9999'],
            'days_min'                => ['required', 'integer', 'min:0', 'max:60'],
            'days_max'                => ['required', 'integer', 'min:0', 'max:60', 'gte:days_min'],
            'is_default'              => ['boolean'],
            'is_active'               => ['boolean'],
            'sort_order'              => ['nullable', 'integer'],
            'allowed_payment_methods' => ['nullable', 'array', 'min:1'],
            'allowed_payment_methods.*' => ['string', 'in:online,cod'],
        ], [], [
            'name'     => 'ชื่อบริการ',
            'fee'      => 'ค่าจัดส่ง',
            'days_min' => 'วันขั้นต่ำ',
            'days_max' => 'วันสูงสุด',
        ]);

        if ($validated['is_default'] ?? false) {
            SellerShippingOption::whereNull('seller_group_id')->update(['is_default' => false]);
        }

        $validated['seller_group_id']        = null;
        $validated['allowed_payment_methods'] = $validated['allowed_payment_methods'] ?? ['online'];
        $option = SellerShippingOption::create($validated);
        return response()->json($option, 201);
    }

    public function update(Request $request, SellerShippingOption $option): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะผู้ดูแลระบบเท่านั้นที่จัดการบริการจัดส่งได้');
        abort_unless(is_null($option->seller_group_id), 404);

        $validated = $request->validate([
            'name'                    => ['sometimes', 'string', 'max:100'],
            'carrier'                 => ['nullable', 'string', 'max:60'],
            'fee'                     => ['sometimes', 'numeric', 'min:0', 'max:9999'],
            'days_min'                => ['sometimes', 'integer', 'min:0', 'max:60'],
            'days_max'                => ['sometimes', 'integer', 'min:0', 'max:60'],
            'is_default'              => ['boolean'],
            'is_active'               => ['boolean'],
            'sort_order'              => ['nullable', 'integer'],
            'allowed_payment_methods' => ['nullable', 'array', 'min:1'],
            'allowed_payment_methods.*' => ['string', 'in:online,cod'],
        ]);

        if ($validated['is_default'] ?? false) {
            SellerShippingOption::whereNull('seller_group_id')
                ->where('id', '!=', $option->id)
                ->update(['is_default' => false]);
        }

        $option->update($validated);
        return response()->json($option);
    }

    public function destroy(Request $request, SellerShippingOption $option): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'เฉพาะผู้ดูแลระบบเท่านั้นที่จัดการบริการจัดส่งได้');
        abort_unless(is_null($option->seller_group_id), 404);
        $option->delete();
        return response()->json(['message' => 'ลบบริการจัดส่งแล้ว']);
    }
}
