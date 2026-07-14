<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartSyncController extends Controller
{
    /**
     * ซิงค์ตะกร้า localStorage ขึ้น server
     * เรียกหลัง login / เมื่อเพิ่มสินค้าในขณะ login แล้ว
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'            => ['required', 'array', 'max:50'],
            'items.*.slug'     => ['required', 'string', 'max:255'],
            'items.*.qty'      => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.added_at' => ['required', 'date'],
        ]);

        $userId = $request->user()->id;
        $slugs  = array_column($data['items'], 'slug');
        $products = Product::whereIn('slug', $slugs)->pluck('id', 'slug');

        foreach ($data['items'] as $item) {
            $productId = $products[$item['slug']] ?? null;
            if (!$productId) continue;

            CartItem::updateOrCreate(
                ['user_id' => $userId, 'product_id' => $productId],
                [
                    'slug'     => $item['slug'],
                    'qty'      => $item['qty'],
                    'added_at' => $item['added_at'],
                ],
            );
        }

        // ลบสินค้าที่ไม่มีในตะกร้าแล้ว (เฉพาะ product ที่ resolve slug ได้)
        $resolvedIds = $products->values()->all();
        if ($resolvedIds) {
            CartItem::where('user_id', $userId)
                ->whereNotIn('product_id', $resolvedIds)
                ->delete();
        }

        return response()->json(['ok' => true]);
    }

    /** ล้างตะกร้า server (เรียกหลัง checkout) */
    public function clear(Request $request): JsonResponse
    {
        CartItem::where('user_id', $request->user()->id)->delete();
        return response()->json(['ok' => true]);
    }
}
