<?php

namespace App\Http\Controllers\Api\Shop;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\SellerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChatController extends Controller
{
    // GET /shop/chat/conversations — รายการสนทนาของลูกค้า
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversations = Conversation::where('customer_id', $user->id)
            ->with(['sellerGroup:id,name,slug,logo_path', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json($conversations);
    }

    // POST /shop/chat/start/{groupSlug} — เริ่มบทสนทนากับกลุ่มผู้ขาย (หรือดึงที่มีอยู่)
    public function start(Request $request, string $slug): JsonResponse
    {
        $user  = $request->user();
        $group = SellerGroup::where('slug', $slug)->firstOrFail();

        $conv = Conversation::firstOrCreate(
            ['seller_group_id' => $group->id, 'customer_id' => $user->id],
            ['last_message_at' => now(), 'is_read_by_seller' => false, 'is_read_by_customer' => true]
        );

        return response()->json($conv->load('sellerGroup:id,name,slug,logo_path'));
    }

    // GET /shop/chat/conversations/{id}/messages — ข้อความในบทสนทนา
    public function messages(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conv = Conversation::where('id', $id)->where('customer_id', $user->id)->firstOrFail();

        // ทำเครื่องหมายว่าลูกค้าอ่านแล้ว
        $conv->update(['is_read_by_customer' => true]);
        ChatMessage::where('conversation_id', $id)->where('sender_type', 'staff')->update(['is_read' => true]);

        $messages = $conv->messages()
            ->with(['sender:id,name', 'product'])
            ->orderBy('created_at')
            ->get()
            ->each(function ($msg) {
                if ($msg->product) {
                    $msg->product->append('primary_image_url');
                }
            });

        return response()->json($messages);
    }

    // POST /shop/chat/conversations/{id}/messages — ลูกค้าส่งข้อความ
    public function send(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'body'       => 'required|string|max:1000',
            'product_id' => 'nullable|integer|exists:products,id',
        ]);
        $user = $request->user();
        $conv = Conversation::where('id', $id)->where('customer_id', $user->id)->firstOrFail();

        $msg = ChatMessage::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'customer',
            'body'            => $request->body,
            'product_id'      => $request->product_id,
        ]);

        $preview = mb_substr($request->body, 0, 80);
        $conv->update([
            'last_message_at'      => now(),
            'last_message_preview' => $preview,
            'is_read_by_seller'    => false,
            'is_read_by_customer'  => true,
        ]);

        $msg->load(['sender:id,name', 'product']);
        if ($msg->product) {
            $msg->product->append('primary_image_url');
        }

        return response()->json($msg, 201);
    }

    // GET /shop/chat/unread — จำนวนข้อความยังไม่ได้อ่าน (สำหรับ badge)
    public function unread(Request $request): JsonResponse
    {
        $count = Conversation::where('customer_id', $request->user()->id)
            ->where('is_read_by_customer', false)
            ->count();
        return response()->json(['count' => $count]);
    }
}
