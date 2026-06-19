<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ยิงข้อความ push ไปยัง LINE ผ่าน Messaging API (OA กลาง)
 * ถ้ายังไม่ตั้งค่า token/target → ข้ามเงียบ (ไม่ทำให้คำสั่งซื้อล้มเหลว)
 */
class SendLineNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public string $targetId,
        public string $message,
    ) {}

    public function handle(): void
    {
        $token = config('services.line.channel_access_token');

        if (empty($token) || empty($this->targetId)) {
            // ยังไม่ได้ตั้งค่า — ข้ามแบบเงียบ
            return;
        }

        $response = Http::withToken($token)
            ->timeout(10)
            ->post(config('services.line.push_url'), [
                'to'       => $this->targetId,
                'messages' => [
                    ['type' => 'text', 'text' => mb_substr($this->message, 0, 4900)],
                ],
            ]);

        if ($response->failed()) {
            Log::warning('LINE push failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'target' => $this->targetId,
            ]);
            // โยน exception เพื่อให้ queue retry
            $response->throw();
        }
    }
}
