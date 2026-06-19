<?php

namespace App\Http\Controllers\Api;

use App\Models\SellerGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * LINE Messaging API Webhook
 * ใช้จับ group_id / user_id เพื่อเก็บใน seller_groups.line_target_id
 *
 * วิธีใช้:
 *  1. ตั้ง Webhook URL ใน LINE Developers Console → Messaging API → Webhook URL
 *     → https://yourdomain.com/api/line/webhook
 *  2. เปิด "Use Webhook" + บันทึก
 *  3. เชิญ OA เข้า LINE Group ที่ต้องการรับแจ้งเตือน
 *  4. ส่งข้อความใดๆ ในกลุ่ม → ระบบจะ log groupId ออกมา
 *  5. นำ groupId ไปใส่ใน หน้าจัดการกลุ่มผู้ขาย → ฟิลด์ LINE Target ID
 */
class LineWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // ตรวจลายเซ็น HMAC ถ้าตั้ง secret ไว้
        if ($secret = config('services.line.channel_secret')) {
            $signature = $request->header('X-Line-Signature', '');
            $expected  = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));
            if (!hash_equals($expected, $signature)) {
                return response('Unauthorized', 401);
            }
        }

        $events = $request->input('events', []);

        foreach ($events as $event) {
            $src  = $event['source'] ?? [];
            $type = $src['type'] ?? '';

            // ข้อความเข้ามาจาก group หรือ user
            if ($event['type'] === 'message') {
                $targetId = match ($type) {
                    'group' => $src['groupId'] ?? null,
                    'room'  => $src['roomId']  ?? null,
                    'user'  => $src['userId']  ?? null,
                    default => null,
                };

                if ($targetId) {
                    Log::info('LINE webhook target detected', [
                        'source_type' => $type,
                        'target_id'   => $targetId,
                        'event_type'  => $event['type'],
                    ]);

                    // ถ้ามีกลุ่มที่ยังไม่มี target_id → เก็บตัวแรกอัตโนมัติ
                    // (แนะนำให้ตั้งด้วยมือใน UI เพื่อความแม่นยำ)
                    $unset = SellerGroup::whereNull('line_target_id')
                        ->where('line_notify_enabled', true)
                        ->first();

                    if ($unset) {
                        $unset->update(['line_target_id' => $targetId]);
                        Log::info('LINE target_id auto-saved', ['group' => $unset->name, 'target_id' => $targetId]);
                    }
                }
            }
        }

        return response('OK', 200);
    }

    /** ดู log ล่าสุดที่จับได้ (admin เท่านั้น — ใช้ตรวจสอบ groupId) */
    public function recentTargets(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $groups = SellerGroup::select('id', 'name', 'line_target_id', 'line_notify_enabled')
            ->orderBy('name')->get();

        return response()->json([
            'groups'  => $groups,
            'tip'     => 'เชิญ OA เข้า LINE Group แล้วส่งข้อความ → ดู line_target_id ที่จับได้จาก log storage/logs/laravel.log',
        ]);
    }
}
