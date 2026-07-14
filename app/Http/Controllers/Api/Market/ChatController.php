<?php

namespace App\Http\Controllers\Api\Market;

use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    // GET /market/chat/conversations — รายการสนทนาของผู้ขาย
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Conversation::with(['customer:id,name,avatar_path', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at');

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort_unless($user->seller_group_id, 403, 'ไม่มีกลุ่มผู้ขาย');
            $q->where('seller_group_id', $user->seller_group_id);
        }

        return response()->json($q->get());
    }

    // GET /market/chat/conversations/{id}/messages
    public function messages(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $conv = Conversation::findOrFail($id);

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort_unless($conv->seller_group_id === $user->seller_group_id, 403);
        }

        $conv->update(['is_read_by_seller' => true]);
        ChatMessage::where('conversation_id', $id)->where('sender_type', 'customer')->update(['is_read' => true]);

        $messages = $conv->messages()
            ->with(['sender:id,name,avatar_path', 'product', 'replyTo'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->each(function ($msg) {
                if ($msg->product) {
                    $msg->product->append('primary_image_url');
                }
                $msg->append('image_url');
            });

        return response()->json($messages);
    }

    // POST /market/chat/conversations/{id}/messages — ผู้ขายตอบ (รองรับ text และ image)
    public function send(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'body'        => 'nullable|string|max:1000',
            'image'       => ['nullable', 'file', 'max:5120'],
            'reply_to_id' => 'nullable|integer|exists:chat_messages,id',
        ]);

        abort_unless(
            $request->filled('body') || $request->hasFile('image'),
            422,
            'กรุณาพิมพ์ข้อความหรือแนบภาพ'
        );

        $user = $request->user();
        $conv = Conversation::findOrFail($id);

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort_unless($conv->seller_group_id === $user->seller_group_id, 403);
        }

        // ตรวจว่าข้อความที่ตอบกลับอยู่ในบทสนทนาเดียวกัน
        $replyToId = $request->input('reply_to_id');
        if ($replyToId && !ChatMessage::where('id', $replyToId)->where('conversation_id', $conv->id)->exists()) {
            $replyToId = null;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store("chat-images/{$id}", 'public');
        }

        $body = $request->input('body', '') ?: '';

        $msg = ChatMessage::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'staff',
            'body'            => $body,
            'image_path'      => $imagePath,
            'reply_to_id'     => $replyToId,
        ]);

        $preview = $body ?: '[ภาพ]';
        $conv->update([
            'last_message_at'      => now(),
            'last_message_preview' => mb_substr($preview, 0, 80),
            'is_read_by_seller'    => true,
            'is_read_by_customer'  => false,
        ]);

        $msg->load(['sender:id,name', 'replyTo']);
        $msg->append('image_url');

        return response()->json($msg, 201);
    }

    // DELETE /market/chat/conversations/{id}/messages/{message} — ลบข้อความของเจ้าหน้าที่เอง
    public function deleteMessage(Request $request, int $id, ChatMessage $message): JsonResponse
    {
        $user = $request->user();
        $conv = Conversation::findOrFail($id);

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort_unless($conv->seller_group_id === $user->seller_group_id, 403);
        }

        abort_unless($message->conversation_id === $conv->id, 403, 'ข้อความนี้ไม่ใช่ในบทสนทนานี้');
        abort_unless($message->sender_type === 'staff', 403, 'ลบได้เฉพาะข้อความที่เจ้าหน้าที่ส่งเอง');

        if ($message->image_path) {
            Storage::disk('public')->delete($message->image_path);
        }
        $message->delete();

        $last = $conv->messages()->latest()->first();
        $conv->update(['last_message_preview' => $last?->body ?: ($last ? '[ภาพ]' : '')]);

        return response()->json(['message' => 'ลบข้อความแล้ว']);
    }

    // GET /market/chat/unread — badge count สำหรับผู้ขาย
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Conversation::where('is_read_by_seller', false);
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            $q->where('seller_group_id', $user->seller_group_id ?? 0);
        }
        return response()->json(['count' => $q->count()]);
    }
}
