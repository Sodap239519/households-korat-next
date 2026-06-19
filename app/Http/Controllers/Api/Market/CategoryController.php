<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * หลังบ้าน — จัดการหมวดหมู่สินค้า
 * หมวดกลาง (seller_group_id = null) แก้ได้เฉพาะ admin; หมวดของกลุ่ม แก้ได้โดยสมาชิกกลุ่ม
 */
class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $query = ProductCategory::withCount('products')->with('sellerGroup:id,name');

        // เห็นหมวดกลาง + หมวดของกลุ่มตัวเอง (admin เห็นทุกหมวด)
        if ($scope = $user->sellerGroupScope()) {
            $query->where(fn ($q) => $q->whereNull('seller_group_id')->orWhere('seller_group_id', $scope));
        }

        return response()->json(
            $query->orderBy('sort_order')->orderBy('name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เพิ่มหมวดหมู่');

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'parent_id'   => ['nullable', 'exists:product_categories,id'],
            'sort_order'  => ['nullable', 'integer'],
            'is_active'   => ['boolean'],
            'is_global'   => ['boolean'], // admin เท่านั้น
        ]);

        $groupId = $user->seller_group_id;
        if ($user->isAdmin() && ($validated['is_global'] ?? false)) {
            $groupId = null;
        }

        $category = ProductCategory::create([
            'seller_group_id' => $groupId,
            'parent_id'       => $validated['parent_id'] ?? null,
            'name'            => $validated['name'],
            'slug'            => $this->uniqueSlug($validated['name'], $groupId),
            'sort_order'      => $validated['sort_order'] ?? 0,
            'is_active'       => $validated['is_active'] ?? true,
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, ProductCategory $category): JsonResponse
    {
        $this->authorizeCategory($request, $category);

        $validated = $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'parent_id'  => ['nullable', 'exists:product_categories,id'],
            'sort_order' => ['nullable', 'integer'],
            'is_active'  => ['boolean'],
        ]);

        $category->update($validated);
        return response()->json($category);
    }

    public function destroy(Request $request, ProductCategory $category): JsonResponse
    {
        $this->authorizeCategory($request, $category);
        abort_if($category->products()->exists(), 422, 'ลบไม่ได้ — ยังมีสินค้าในหมวดนี้');
        $category->delete();
        return response()->json(['message' => 'ลบหมวดหมู่แล้ว']);
    }

    private function authorizeCategory(Request $request, ProductCategory $category): void
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');
        // หมวดกลาง → admin เท่านั้น; หมวดกลุ่ม → สมาชิกกลุ่มนั้น
        if (is_null($category->seller_group_id)) {
            abort_unless($user->isAdmin(), 403, 'หมวดกลางแก้ไขได้เฉพาะผู้ดูแลระบบ');
        } else {
            abort_unless($user->canActInGroup($category->seller_group_id), 403, 'หมวดนี้อยู่นอกกลุ่มที่คุณดูแล');
        }
    }

    private function uniqueSlug(string $name, ?int $groupId): string
    {
        $base = ProductController::thaiSlug($name) ?: 'category';
        $slug = $base;
        $i = 1;
        while (ProductCategory::where('seller_group_id', $groupId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
