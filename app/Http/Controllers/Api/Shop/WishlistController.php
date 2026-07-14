<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /** รายการโปรดพร้อมข้อมูลสินค้า */
    public function index(Request $request): JsonResponse
    {
        $items = WishlistItem::where('user_id', $request->user()->id)
            ->with(['product' => function ($q) {
                $q->with(['images', 'sellerGroup:id,name,slug'])
                  ->withCount('reviews');
            }])
            ->latest('created_at')
            ->paginate(20);

        return response()->json($items);
    }

    /** คืน array ของ product_id ที่อยู่ในรายการโปรด (สำหรับ init ฝั่ง client) */
    public function ids(Request $request): JsonResponse
    {
        $ids = WishlistItem::where('user_id', $request->user()->id)
            ->pluck('product_id');

        return response()->json($ids);
    }

    /** สลับชอบ/ไม่ชอบ (toggle) */
    public function toggle(Request $request, int $productId): JsonResponse
    {
        abort_unless(Product::where('id', $productId)->where('status', 'published')->exists(), 404, 'ไม่พบสินค้า');

        $existing = WishlistItem::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['in_wishlist' => false]);
        }

        WishlistItem::create([
            'user_id'    => $request->user()->id,
            'product_id' => $productId,
        ]);

        return response()->json(['in_wishlist' => true]);
    }
}
