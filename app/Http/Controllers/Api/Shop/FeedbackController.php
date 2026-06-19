<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ลูกค้าโพสต์รีวิว + คอมเมนต์ที่หน้าร้าน
 */
class FeedbackController extends Controller
{
    /** ตรวจว่าลูกค้าซื้อสินค้านี้แล้วหรือไม่ (สำหรับปุ่มรีวิวฝั่งหน้าร้าน) */
    public function eligibility(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $order = $this->eligibleOrder($request, $product);

        return response()->json([
            'can_review'  => (bool) $order && !$this->alreadyReviewed($request, $product, $order),
            'can_comment' => true,
        ]);
    }

    public function storeReview(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('status', Product::STATUS_PUBLISHED)->where('slug', $slug)->firstOrFail();

        $order = $this->eligibleOrder($request, $product);
        abort_unless($order, 403, 'ต้องสั่งซื้อและได้รับสินค้านี้ก่อนจึงจะรีวิวได้');
        abort_if($this->alreadyReviewed($request, $product, $order), 422, 'คุณรีวิวสินค้านี้จากคำสั่งซื้อนี้แล้ว');

        $data = $request->validate([
            'rating'   => ['required', 'integer', 'min:1', 'max:5'],
            'title'    => ['nullable', 'string', 'max:255'],
            'comment'  => ['nullable', 'string', 'max:2000'],
            'images'   => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:4096'],
        ], [], ['rating' => 'คะแนน']);

        $paths = [];
        foreach ($request->file('images', []) as $file) {
            $paths[] = $file->store("reviews/{$product->id}", 'public');
        }

        $review = $product->reviews()->create([
            'order_id' => $order->id,
            'user_id'  => $request->user()->id,
            'rating'   => $data['rating'],
            'title'    => $data['title'] ?? null,
            'comment'  => $data['comment'] ?? null,
            'images'   => $paths ?: null,
            'status'   => ProductReview::STATUS_PUBLISHED,
        ]);

        $product->recalculateRating();

        return response()->json(['message' => 'ขอบคุณสำหรับรีวิว', 'review' => $review->load('user:id,name')], 201);
    }

    public function storeComment(Request $request, string $slug): JsonResponse
    {
        $product = Product::where('status', Product::STATUS_PUBLISHED)->where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'body'      => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'exists:product_comments,id'],
        ], [], ['body' => 'ข้อความ']);

        $comment = $product->comments()->create([
            'user_id'   => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body'      => $data['body'],
            'status'    => ProductComment::STATUS_PUBLISHED,
        ]);

        return response()->json(['message' => 'ส่งความคิดเห็นแล้ว', 'comment' => $comment->load('user:id,name')], 201);
    }

    private function eligibleOrder(Request $request, Product $product): ?Order
    {
        return Order::where('user_id', $request->user()->id)
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest()
            ->first();
    }

    private function alreadyReviewed(Request $request, Product $product, Order $order): bool
    {
        return ProductReview::where('product_id', $product->id)
            ->where('order_id', $order->id)
            ->where('user_id', $request->user()->id)
            ->exists();
    }
}
