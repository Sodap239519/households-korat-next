<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * หลังบ้าน — จัดการรีวิวสินค้า (ตอบกลับ / ซ่อน) scope ตามกลุ่ม
 */
class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $query = ProductReview::query()
            ->with(['product:id,name,slug,seller_group_id', 'user:id,name'])
            ->whereHas('product', function ($q) use ($user, $request) {
                if ($scope = $user->sellerGroupScope()) $q->where('seller_group_id', $scope);
                elseif ($request->filled('group_id'))   $q->where('seller_group_id', $request->input('group_id'));
            });

        if ($request->filled('status')) $query->where('status', $request->input('status'));

        return response()->json($query->orderByDesc('created_at')->paginate($request->input('per_page', 20)));
    }

    public function reply(Request $request, ProductReview $review): JsonResponse
    {
        $this->authorizeReview($request, $review);
        $data = $request->validate(['reply' => ['required', 'string', 'max:1000']], [], ['reply' => 'คำตอบ']);

        $review->update(['reply' => $data['reply'], 'replied_by' => $request->user()->id]);
        return response()->json(['message' => 'ตอบกลับแล้ว', 'review' => $review->fresh()]);
    }

    public function setStatus(Request $request, ProductReview $review): JsonResponse
    {
        $this->authorizeReview($request, $review);
        $data = $request->validate(['status' => ['required', 'in:published,hidden']]);

        $review->update(['status' => $data['status']]);
        $review->product?->recalculateRating(); // รีวิวที่ซ่อนไม่นับคะแนน

        return response()->json(['message' => 'อัปเดตแล้ว', 'review' => $review->fresh()]);
    }

    private function authorizeReview(Request $request, ProductReview $review): void
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');
        abort_unless($user->canActInGroup($review->product?->seller_group_id), 403, 'รีวิวนี้อยู่นอกกลุ่มที่คุณดูแล');
    }
}
