<?php

namespace App\Http\Controllers\Api\Shop;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\SellerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    // GET /shop/chat/conversations — รายการสนทนาของลูกค้า
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversations = Conversation::where('customer_id', $user->id)
            ->with([
                'sellerGroup:id,name,slug,logo_path',
                'sellerUser:id,name,shop_name,avatar_path',
            ])
            ->orderByDesc('last_message_at')
            ->get();

        if ($conversations->isNotEmpty()) {
            $convIds = $conversations->pluck('id');

            // preview ข้อความล่าสุด (sort id ด้วยเพื่อ stable ordering)
            $latestMsgs = ChatMessage::whereIn('conversation_id', $convIds)
                ->select('conversation_id', 'body', 'image_path', 'created_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->unique('conversation_id');

            // หา id ข้อความแรกที่ยังไม่อ่าน (staff+is_read=false) ต่อแต่ละ conversation ที่ยังไม่ได้อ่าน
            $unreadConvIds = $conversations->where('is_read_by_customer', false)->pluck('id');
            $firstUnreadMap = [];
            if ($unreadConvIds->isNotEmpty()) {
                DB::table('chat_messages')
                    ->whereIn('conversation_id', $unreadConvIds)
                    ->where('sender_type', 'staff')
                    ->where('is_read', false)
                    ->select('conversation_id', DB::raw('MIN(id) as first_id'))
                    ->groupBy('conversation_id')
                    ->get()
                    ->each(fn($r) => $firstUnreadMap[$r->conversation_id] = $r->first_id);
            }

            // conversation เก่าที่ seller_user_id = null:
            // ถ้าโซนนั้นมี "ร้านค้าจริง" (มี shop_name) เพียงร้านเดียว → ถือว่าแชทกับร้านนั้น
            // (บัญชีเจ้าหน้าที่โซนที่ shop_name=NULL ไม่นับ — กันแชทระดับโซนแสดงชื่อเจ้าหน้าที่ผิด)
            $nullConvGroupIds = $conversations->where('seller_user_id', null)
                ->pluck('seller_group_id')->unique();
            if ($nullConvGroupIds->isNotEmpty()) {
                $singleShopMap = \App\Models\User::whereIn('seller_group_id', $nullConvGroupIds)
                    ->where('is_approved', true)
                    ->whereNotNull('shop_name')
                    ->select('id', 'name', 'shop_name', 'avatar_path', 'seller_group_id')
                    ->get()
                    ->groupBy('seller_group_id')
                    ->filter(fn ($g) => $g->count() === 1)
                    ->map(fn ($g) => $g->first());

                foreach ($conversations as $conv) {
                    if ($conv->seller_user_id === null && isset($singleShopMap[$conv->seller_group_id])) {
                        $conv->setRelation('sellerUser', $singleShopMap[$conv->seller_group_id]);
                    }
                }
            }

            foreach ($conversations as $conv) {
                $last = $latestMsgs->firstWhere('conversation_id', $conv->id);
                if ($last) {
                    $conv->last_message_preview = $last->body ?: '[ภาพ]';
                }
                $conv->first_unread_message_id = $firstUnreadMap[$conv->id] ?? null;
            }
        }

        return response()->json($conversations);
    }

    // POST /shop/chat/start/{groupSlug} — เริ่มบทสนทนากับกลุ่มผู้ขาย (หรือดึงที่มีอยู่)
    // ?member=user_id ระบุร้านค้าย่อยที่ต้องการคุยด้วย
    public function start(Request $request, string $slug): JsonResponse
    {
        $user      = $request->user();
        $group     = SellerGroup::where('slug', $slug)->firstOrFail();
        $sellerId  = $request->input('member') ? (int) $request->input('member') : null;

        // ตรวจว่า seller_user_id ที่ระบุมาอยู่ในกลุ่มนี้จริง
        if ($sellerId) {
            $sellerExists = \App\Models\User::where('id', $sellerId)
                ->where('seller_group_id', $group->id)
                ->exists();
            if (!$sellerExists) $sellerId = null;
        }

        // ค้นหา conversation เดิมของลูกค้ากับกลุ่มนี้ (ถ้ามี) เพื่อใช้ต่อ ไม่ว่าจะเคยสร้างจาก sub-store ไหน
        $conv = Conversation::firstOrCreate(
            ['seller_group_id' => $group->id, 'customer_id' => $user->id],
            ['seller_user_id' => $sellerId, 'last_message_at' => now(), 'is_read_by_seller' => false, 'is_read_by_customer' => true]
        );

        // ถ้า conversation เดิมยังไม่มี seller_user_id แต่ตอนนี้รู้แล้ว → อัปเดต
        if ($sellerId && $conv->seller_user_id === null) {
            $conv->update(['seller_user_id' => $sellerId]);
        }

        return response()->json($conv->load([
            'sellerGroup:id,name,slug,logo_path',
            'sellerUser:id,name,shop_name,avatar_path',
        ]));
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
            ->with(['sender:id,name', 'product', 'replyTo'])
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

    // POST /shop/chat/conversations/{id}/messages — ลูกค้าส่งข้อความ (รองรับทั้ง text และ image)
    public function send(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'body'        => 'nullable|string|max:1000',
            'product_id'  => 'nullable|integer|exists:products,id',
            'image'       => ['nullable', 'file', 'max:5120'],
            'reply_to_id' => 'nullable|integer|exists:chat_messages,id',
        ]);

        abort_unless(
            $request->filled('body') || $request->hasFile('image'),
            422,
            'กรุณาพิมพ์ข้อความหรือแนบภาพ'
        );

        $user = $request->user();
        $conv = Conversation::where('id', $id)->where('customer_id', $user->id)->firstOrFail();

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
            'sender_type'     => 'customer',
            'body'            => $body,
            'product_id'      => $request->product_id,
            'image_path'      => $imagePath,
            'reply_to_id'     => $replyToId,
        ]);

        $preview = $body ?: '[ภาพ]';
        $conv->update([
            'last_message_at'      => now(),
            'last_message_preview' => mb_substr($preview, 0, 80),
            'is_read_by_seller'    => false,
            'is_read_by_customer'  => true,
        ]);

        $msg->load(['sender:id,name', 'product', 'replyTo']);
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
