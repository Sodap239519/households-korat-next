<?php

namespace App\Http\Controllers\Api\Market;

use App\Models\ChatMessage;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChatController extends Controller
{
    // GET /market/chat/conversations — รายการสนทนาของผู้ขาย
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Conversation::with(['customer:id,name', 'messages' => fn($q) => $q->latest()->limit(1)])
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

        return response()->json($conv->messages()->with('sender:id,name')->orderBy('created_at')->get());
    }

    // POST /market/chat/conversations/{id}/messages — ผู้ขายตอบ
    public function send(Request $request, int $id): JsonResponse
    {
        $request->validate(['body' => 'required|string|max:1000']);
        $user = $request->user();
        $conv = Conversation::findOrFail($id);

        if (!in_array($user->role, ['admin', 'superadmin'])) {
            abort_unless($conv->seller_group_id === $user->seller_group_id, 403);
        }

        $msg = ChatMessage::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'sender_type'     => 'staff',
            'body'            => $request->body,
        ]);

        $conv->update([
            'last_message_at'      => now(),
            'last_message_preview' => mb_substr($request->body, 0, 80),
            'is_read_by_seller'    => true,
            'is_read_by_customer'  => false,
        ]);

        return response()->json($msg->load('sender:id,name'), 201);
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
