<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CustomerNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * หลังบ้าน — จัดการสินค้า (scope ตามกลุ่ม; admin เห็นทุกกลุ่ม)
 */
class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $query = Product::query()
            ->with(['images', 'category:id,name', 'sellerGroup:id,name', 'options'])
            ->withCount('reviews');

        // scope: ผู้ขายทั่วไปเห็นเฉพาะสินค้าตัวเอง, admin/superadmin เห็นทั้งกลุ่ม/ทั้งหมด
        if (($personal = $user->sellerPersonalScope()) !== null) {
            $query->where('seller_user_id', $personal);
        } elseif (($scope = $user->sellerGroupScope()) !== null) {
            $query->where('seller_group_id', $scope);
        } elseif ($request->filled('group_id')) {
            $query->where('seller_group_id', $request->input('group_id'));
        }

        if ($q = trim((string) $request->input('q'))) {
            $query->where(fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"));
        }
        if ($request->filled('status'))      $query->where('status', $request->input('status'));
        if ($request->filled('category_id')) $query->where('category_id', $request->input('category_id'));

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->input('per_page', 20))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เพิ่มสินค้า');

        $validated = $this->validateData($request);
        $groupId   = $this->resolveGroupId($request, $user);

        abort_unless($user->canActInGroup($groupId), 403, 'ไม่มีสิทธิ์เพิ่มสินค้าในกลุ่มนี้');

        $product = new Product($validated);
        $product->seller_group_id = $groupId;
        $product->seller_user_id  = $user->isAdmin() ? null : $user->id;
        $product->slug = $this->uniqueSlug($validated['name']);
        $product->save();

        $this->syncOptions($product, $request->input('options'));

        return response()->json($product->load(['images', 'options']), 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);
        return response()->json($product->load(['images', 'category:id,name', 'sellerGroup:id,name', 'options']));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        // บันทึกราคาเดิมก่อน fill เพื่อตรวจว่าลดราคาหรือไม่
        $oldPrice = (float) $product->effective_price;

        $validated = $this->validateData($request, $product);
        $product->fill($validated);

        // admin ย้ายกลุ่มได้
        if ($request->user()->isAdmin() && $request->filled('seller_group_id')) {
            $product->seller_group_id = (int) $request->input('seller_group_id');
        }
        $product->save();

        $this->syncOptions($product, $request->input('options'));

        // แจ้งเตือนโปรโมชั่นเมื่อราคาลด
        $newPrice = (float) $product->fresh()->effective_price;
        if ($newPrice < $oldPrice) {
            CustomerNotificationService::promotion($product, $oldPrice, $newPrice);
        }

        return response()->json($product->load(['images', 'options']));
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);
        $product->delete(); // soft delete
        return response()->json(['message' => 'ลบสินค้าแล้ว']);
    }

    /** อัปโหลดรูปสินค้า (หลายไฟล์) */
    public function uploadImages(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        $request->validate([
            'images'   => ['required', 'array', 'max:8'],
            'images.*' => ['image', 'max:4096'], // 4MB/รูป
        ], [], ['images.*' => 'รูปภาพ']);

        $hasPrimary = $product->images()->where('is_primary', true)->exists();
        $order = (int) $product->images()->max('sort_order');

        foreach ($request->file('images') as $file) {
            $path = $file->store("products/{$product->id}", 'public');
            $product->images()->create([
                'path'       => $path,
                'sort_order' => ++$order,
                'is_primary' => !$hasPrimary,
            ]);
            $hasPrimary = true;
        }

        return response()->json($product->load('images'));
    }

    /** ลบรูปสินค้า */
    public function deleteImage(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $this->authorizeProduct($request, $product);
        abort_unless($image->product_id === $product->id, 404);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->path);
        $wasPrimary = $image->is_primary;
        $image->delete();

        // ถ้าลบรูปหลัก → เลื่อนรูปแรกที่เหลือเป็นรูปหลัก
        if ($wasPrimary) {
            $next = $product->images()->orderBy('sort_order')->first();
            $next?->update(['is_primary' => true]);
        }

        return response()->json($product->load('images'));
    }

    public function flashSaleList(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Product::whereNotNull('sale_price')
            ->with(['images' => fn($q) => $q->where('is_primary', true)])
            ->orderBy('flash_sale_end');

        if (($personal = $user->sellerPersonalScope()) !== null) {
            $q->where('seller_user_id', $personal);
        } elseif (!$user->isSuperAdmin() && $user->seller_group_id) {
            $q->where('seller_group_id', $user->seller_group_id);
        }

        return response()->json($q->get(['id', 'name', 'sku', 'price', 'sale_price', 'stock_qty', 'flash_sale_start', 'flash_sale_end', 'status', 'seller_group_id']));
    }

    public function flashSaleBatch(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'action'            => ['required', 'in:set,remove'],
            'product_ids'       => ['required', 'array', 'min:1'],
            'product_ids.*'     => ['integer', 'exists:products,id'],
            'sale_price'        => ['required_if:action,set', 'nullable', 'numeric', 'min:0'],
            'flash_sale_start'  => ['nullable', 'date'],
            'flash_sale_end'    => ['nullable', 'date'],
        ]);

        $q = Product::whereIn('id', $data['product_ids']);
        if (($personal = $user->sellerPersonalScope()) !== null) {
            $q->where('seller_user_id', $personal);
        } elseif (!$user->isSuperAdmin() && $user->seller_group_id) {
            $q->where('seller_group_id', $user->seller_group_id);
        }

        if ($data['action'] === 'remove') {
            $q->update(['sale_price' => null, 'flash_sale_start' => null, 'flash_sale_end' => null]);
        } else {
            $q->update([
                'sale_price'       => $data['sale_price'],
                'flash_sale_start' => $data['flash_sale_start'] ?? null,
                'flash_sale_end'   => $data['flash_sale_end'] ?? null,
            ]);
        }

        return response()->json(['message' => 'อัปเดตสำเร็จ', 'affected' => $q->count()]);
    }

    // ===== helpers =====

    private function validateData(Request $request, ?Product $product = null): array
    {
        $required = $product ? 'sometimes' : 'required';

        return $request->validate([
            'name'              => [$required, 'string', 'max:255'],
            'category_id'       => ['nullable', 'exists:product_categories,id'],
            'sku'               => ['nullable', 'string', 'max:100'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'price'             => [$required, 'numeric', 'min:0'],
            'sale_price'        => ['nullable', 'numeric', 'min:0'],
            'flash_sale_start'  => ['nullable', 'date'],
            'flash_sale_end'    => ['nullable', 'date', 'after_or_equal:flash_sale_start'],
            'stock_qty'         => ['nullable', 'integer', 'min:0'],
            'unit'              => ['nullable', 'string', 'max:30'],
            'district'          => ['nullable', 'string', 'max:100'],
            'status'              => ['nullable', 'in:draft,published'],
            'is_featured'         => ['boolean'],
            'shipping_option_ids' => ['required', 'array', 'min:1'],
            'shipping_option_ids.*' => ['integer', 'exists:seller_shipping_options,id'],
            // ตัวเลือกสินค้า (ไม่บังคับ) — ถ้ามี แต่ละตัวต้องมีชื่อ/ราคา/สต๊อก
            'options'             => ['nullable', 'array'],
            'options.*.id'        => ['nullable', 'integer'],
            'options.*.name'      => ['required_with:options', 'string', 'max:100'],
            'options.*.price'     => ['required_with:options', 'numeric', 'min:0'],
            'options.*.stock_qty' => ['required_with:options', 'integer', 'min:0'],
        ]);
    }

    /**
     * ซิงค์ตัวเลือกสินค้า + roll-up ราคา(ต่ำสุด)/สต๊อก(รวม) ขึ้นระดับสินค้า
     * เพื่อให้ logic แคตตาล็อก/สต๊อก/สินค้าหมด เดิมทำงานต่อได้
     * - $options = null  → ไม่แตะ (partial update)
     * - $options = []    → ลบตัวเลือกทั้งหมด (กลับเป็นสินค้าธรรมดา)
     */
    private function syncOptions(Product $product, ?array $options): void
    {
        if ($options === null) return;

        $keepIds = [];
        foreach (array_values($options) as $i => $opt) {
            $attrs = [
                'name'       => $opt['name'],
                'price'      => $opt['price'],
                'stock_qty'  => $opt['stock_qty'],
                'sort_order' => $i,
                'is_active'  => $opt['is_active'] ?? true,
            ];
            $existing = !empty($opt['id']) ? $product->options()->whereKey($opt['id'])->first() : null;
            if ($existing) {
                $existing->update($attrs);
                $keepIds[] = $existing->id;
            } else {
                $keepIds[] = $product->options()->create($attrs)->id;
            }
        }
        $product->options()->whereNotIn('id', $keepIds ?: [0])->delete();

        // roll-up: ถ้ามีตัวเลือก → ราคาสินค้า = ต่ำสุด, สต๊อก = รวม, ยกเลิกราคาลดระดับสินค้า
        $product->refresh()->loadMissing('options');
        if ($product->options->isNotEmpty()) {
            $product->update([
                'price'      => $product->options->min('price'),
                'sale_price' => null,
                'stock_qty'  => $product->options->sum('stock_qty'),
            ]);
        }
    }

    private function resolveGroupId(Request $request, $user): int
    {
        if ($user->isAdmin()) {
            return (int) $request->input('seller_group_id', $user->seller_group_id);
        }
        return (int) $user->seller_group_id;
    }

    private function authorizeProduct(Request $request, Product $product): void
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');
        abort_unless($user->canActInGroup($product->seller_group_id), 403, 'สินค้านี้อยู่นอกกลุ่มที่คุณดูแล');
        // staff ทั่วไปแก้เฉพาะสินค้าตัวเอง
        if (!$user->isAdmin()) {
            abort_unless((int) $product->seller_user_id === $user->id, 403, 'คุณไม่ใช่เจ้าของสินค้านี้');
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = self::thaiSlug($name) ?: 'product';
        $slug = $base;
        $i = 1;
        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    /** slug ที่รองรับภาษาไทย (Str::slug ตัดอักขระไทยทิ้ง) */
    public static function thaiSlug(string $value): string
    {
        $value = preg_replace('/[^\p{L}\p{N}\p{M}\s-]+/u', '', $value);   // ตัดสัญลักษณ์ (เก็บสระ/วรรณยุกต์ \p{M})
        $value = preg_replace('/[\s_]+/u', '-', trim($value));            // ช่องว่าง → ขีด
        $value = preg_replace('/-+/', '-', $value);
        return mb_strtolower(trim($value, '-'));
    }
}
