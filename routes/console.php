<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ส่งการแจ้งเตือนลูกค้าทุกวัน: กดรับสินค้า + ตะกร้าค้าง
Schedule::command('shop:send-reminders')->dailyAt('08:00');

// ตรวจคำขอสมัครขายที่ admin ยังไม่พิจารณาภายใน 1 ชม → escalate ไป superadmin
Schedule::command('market:escalate-seller-applications')->everyFiveMinutes();
