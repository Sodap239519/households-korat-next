<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\ProductComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * หลังบ้าน — จัดการคอมเมนต์สินค้า (ตอบกลับ / ซ่อน) scope ตามกลุ่ม
 */
class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $query = ProductComment::query()
            ->whereNull('parent_id')
            ->with(['product:id,name,slug,seller_group_id', 'user:id,name', 'replies.user:id,name'])
            ->whereHas('product', function ($q) use ($user, $request) {
                if (($personal = $user->sellerPersonalScope()) !== null) {
                    $q->where('seller_user_id', $personal);
                } elseif ($scope = $user->sellerGroupScope()) {
                    $q->where('seller_group_id', $scope);
                } elseif ($request->filled('group_id')) {
                    $q->where('seller_group_id', $request->input('group_id'));
                }
            });

        if ($request->filled('status')) $query->where('status', $request->input('status'));

        return response()->json($query->orderByDesc('created_at')->paginate($request->input('per_page', 20)));
    }

    public function reply(Request $request, ProductComment $comment): JsonResponse
    {
        $this->authorizeComment($request, $comment);
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']], [], ['body' => 'คำตอบ']);

        $reply = $comment->product->comments()->create([
            'user_id'   => $request->user()->id,
            'parent_id' => $comment->id,
            'body'      => $data['body'],
            'status'    => ProductComment::STATUS_PUBLISHED,
        ]);

        return response()->json(['message' => 'ตอบกลับแล้ว', 'reply' => $reply->load('user:id,name')], 201);
    }

    public function setStatus(Request $request, ProductComment $comment): JsonResponse
    {
        $this->authorizeComment($request, $comment);
        $data = $request->validate(['status' => ['required', 'in:published,hidden']]);

        $comment->update(['status' => $data['status']]);
        return response()->json(['message' => 'อัปเดตแล้ว', 'comment' => $comment->fresh()]);
    }

    private function authorizeComment(Request $request, ProductComment $comment): void
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');
        abort_unless($user->canActInGroup($comment->product?->seller_group_id), 403, 'คอมเมนต์นี้อยู่นอกกลุ่มที่คุณดูแล');
        if (!$user->isAdmin()) {
            abort_unless((int) $comment->product?->seller_user_id === $user->id, 403, 'คอมเมนต์นี้ไม่ใช่สินค้าของคุณ');
        }
    }
}
