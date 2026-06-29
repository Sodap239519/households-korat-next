<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SellerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * หน้าร้าน (storefront) — public, ไม่ต้อง login
 * แสดงเฉพาะกลุ่ม/หมวด/สินค้าที่ active + published
 */
class CatalogController extends Controller
{
    /** รายชื่อกลุ่มผู้ขายที่เปิดใช้งาน */
    public function groups(): JsonResponse
    {
        $groups = SellerGroup::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('status', Product::STATUS_PUBLISHED)])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'logo_path', 'districts', 'contact_phone', 'contact_address', 'lat', 'lng', 'map_label']);

        return response()->json($groups);
    }

    /** หมวดหมู่สินค้าที่เปิดใช้งาน */
    public function categories(Request $request): JsonResponse
    {
        $query = ProductCategory::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('status', Product::STATUS_PUBLISHED)]);

        if ($group = $request->input('group')) {
            $query->where(function ($q) use ($group) {
                $q->whereNull('seller_group_id')
                  ->orWhereHas('sellerGroup', fn ($g) => $g->where('slug', $group));
            });
        }

        return response()->json(
            $query->orderBy('sort_order')->orderBy('name')->get()
        );
    }

    /** รายการสินค้า + filter + paginate */
    public function products(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('status', Product::STATUS_PUBLISHED)
            ->with(['images', 'sellerGroup:id,name,slug', 'category:id,name,slug']);

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('short_description', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->whereHas('category', fn ($c) => $c->where('slug', $category)->orWhere('id', $category));
        }

        if ($group = $request->input('group')) {
            $query->whereHas('sellerGroup', fn ($g) => $g->where('slug', $group)->orWhere('id', $group));
        }

        if ($district = $request->input('district')) {
            $query->where('district', $district);
        }

        if (($min = $request->input('min_price')) !== null && $min !== '') {
            $query->where('price', '>=', (float) $min);
        }
        if (($max = $request->input('max_price')) !== null && $max !== '') {
            $query->where('price', '<=', (float) $max);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        switch ($request->input('sort', 'newest')) {
            case 'price_asc':  $query->orderBy('price');               break;
            case 'price_desc': $query->orderByDesc('price');           break;
            case 'popular':    $query->orderByDesc('view_count');      break;
            case 'rating':     $query->orderByDesc('rating_avg');      break;
            default:           $query->orderByDesc('created_at');      break;
        }

        return response()->json(
            $query->paginate($request->input('per_page', 12))
        );
    }

    /** รายละเอียดสินค้า (by slug) + รูป + รีวิว + คอมเมนต์ */
    public function product(string $slug): JsonResponse
    {
        $product = Product::where('status', Product::STATUS_PUBLISHED)
            ->where('slug', $slug)
            ->with([
                'images',
                'sellerGroup:id,name,slug,description,contact_phone,contact_address',
                'category:id,name,slug',
            ])
            ->firstOrFail();

        // นับยอดเข้าชม (ไม่กระทบ updated_at)
        $product->increment('view_count');

        // ความสนใจ: จำนวนคนที่กดรายการโปรด
        $product->wishlist_count = \App\Models\WishlistItem::where('product_id', $product->id)->count();

        // ยอดขายสะสม (จากออเดอร์ที่จัดส่ง/เสร็จแล้ว)
        $product->total_sold = (int) \App\Models\OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['shipped', 'delivered', 'completed']))
            ->sum('qty');

        $reviews = $product->reviews()
            ->where('status', 'published')
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get();

        $comments = $product->comments()
            ->where('status', 'published')
            ->whereNull('parent_id')
            ->with(['user:id,name', 'replies.user:id,name'])
            ->latest()
            ->limit(20)
            ->get();

        // สินค้าใกล้เคียง (หมวดเดียวกัน)
        $related = Product::where('status', Product::STATUS_PUBLISHED)
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->with('images')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        // สินค้าจากร้านเดียวกัน
        $fromStore = Product::where('status', Product::STATUS_PUBLISHED)
            ->where('id', '!=', $product->id)
            ->where('seller_group_id', $product->seller_group_id)
            ->with('images')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        // สินค้าที่คุณอาจชอบ (ยอดนิยม/ใหม่ จากทั้งร้าน ไม่ซ้ำกับ related/fromStore)
        $excludeIds = $related->pluck('id')
            ->merge($fromStore->pluck('id'))
            ->push($product->id)
            ->unique()->values();

        $alsoLike = Product::where('status', Product::STATUS_PUBLISHED)
            ->whereNotIn('id', $excludeIds)
            ->with('images')
            ->orderByDesc('view_count')
            ->limit(8)
            ->get();

        return response()->json([
            'product'    => $product,
            'reviews'    => $reviews,
            'comments'   => $comments,
            'related'    => $related,
            'from_store' => $fromStore,
            'also_like'  => $alsoLike,
        ]);
    }

    /** หน้าร้านผู้ขาย — ข้อมูลกลุ่ม + สินค้า */
    public function seller(Request $request, string $slug): JsonResponse
    {
        $group = SellerGroup::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $products = Product::where('seller_group_id', $group->id)
            ->where('status', Product::STATUS_PUBLISHED)
            ->with(['images', 'category:id,name,slug'])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate(12);

        $stats = Product::where('seller_group_id', $group->id)
            ->where('status', Product::STATUS_PUBLISHED)
            ->selectRaw('AVG(rating_avg) as avg_rating, SUM(rating_count) as total_reviews')
            ->first();

        return response()->json([
            'group'    => array_merge($group->toArray(), [
                'avg_rating'    => round($stats->avg_rating ?? 0, 1),
                'total_reviews' => (int) ($stats->total_reviews ?? 0),
            ]),
            'products' => $products,
        ]);
    }

    /** รีวิวสินค้า (paginated, สำหรับ lazy-load ในหน้ารายละเอียด) */
    public function productReviews(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $reviews = $product->reviews()
            ->where('status', 'published')
            ->with('user:id,name')
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json($reviews);
    }

    /** คอมเมนต์สินค้า (paginated) */
    public function productComments(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $comments = $product->comments()
            ->where('status', 'published')
            ->whereNull('parent_id')
            ->with(['user:id,name', 'replies.user:id,name'])
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json($comments);
    }
}
