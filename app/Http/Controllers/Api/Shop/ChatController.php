<?php

namespace App\Http\Controllers\Api\Shop;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\SellerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

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
                $msg->append('image_url');
            });

        return response()->json($messages);
    }

    // POST /shop/chat/conversations/{id}/messages — ลูกค้าส่งข้อความ (รองรับทั้ง text และ image)
    public function send(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'body'       => 'nullable|string|max:1000',
            'product_id' => 'nullable|integer|exists:products,id',
            'image'      => ['nullable', 'file', 'max:5120'],
        ]);

        abort_unless(
            $request->filled('body') || $request->hasFile('image'),
            422,
            'กรุณาพิมพ์ข้อความหรือแนบภาพ'
        );

        $user = $request->user();
        $conv = Conversation::where('id', $id)->where('customer_id', $user->id)->firstOrFail();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store("chat-images/{$id}", 'public');
        }

        $body = $request->input('body', '') ?: '';

        $msg = ChatMessage::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'customer',
            'body'            => $body,
            'product_id'      => $request->product_id,
            'image_path'      => $imagePath,
        ]);

        $preview = $body ?: '[ภาพ]';
        $conv->update([
            'last_message_at'      => now(),
            'last_message_preview' => mb_substr($preview, 0, 80),
            'is_read_by_seller'    => false,
            'is_read_by_customer'  => true,
        ]);

        $msg->load(['sender:id,name', 'product']);
        if ($msg->product) {
            $msg->product->append('primary_image_url');
        }
        $msg->append('image_url');

        return response()->json($msg, 201);
    }

    // DELETE /shop/chat/conversations/{id}/messages/{message} — ลบข้อความของตัวเอง
    public function deleteMessage(Request $request, int $id, ChatMessage $message): JsonResponse
    {
        $user = $request->user();
        $conv = Conversation::where('id', $id)->where('customer_id', $user->id)->firstOrFail();

        abort_unless($message->conversation_id === $conv->id, 403, 'ข้อความนี้ไม่ใช่ในบทสนทนานี้');
        abort_unless($message->sender_type === 'customer' && $message->sender_id === $user->id, 403, 'ลบได้เฉพาะข้อความที่คุณส่งเอง');

        if ($message->image_path) {
            Storage::disk('public')->delete($message->image_path);
        }
        $message->delete();

        $last = $conv->messages()->latest()->first();
        $conv->update(['last_message_preview' => $last?->body ?? '']);

        return response()->json(['message' => 'ลบข้อความแล้ว']);
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
