<?php

namespace App\Console\Commands;

use App\Models\SellerApplication;
use Illuminate\Console\Command;

/**
 * ตรวจคำขอสมัครขายที่ admin ยังไม่พิจารณาภายใน ESCALATE_MINUTES นาที
 * แล้วเปลี่ยนสถานะเป็น 'escalated' เพื่อให้ superadmin จัดการโดยตรง
 */
class EscalateSellerApplications extends Command
{
    protected $signature   = 'market:escalate-seller-applications';
    protected $description = 'Escalate seller applications that admin has not reviewed within the time limit';

    public function handle(): int
    {
        $deadline = now()->subMinutes(SellerApplication::ESCALATE_MINUTES);

        $count = SellerApplication::where('status', SellerApplication::STATUS_PENDING)
            ->where('created_at', '<=', $deadline)
            ->update([
                'status'       => SellerApplication::STATUS_ESCALATED,
                'escalated_at' => now(),
            ]);

        if ($count > 0) {
            $this->info("[market:escalate] escalated {$count} application(s) to superadmin.");
        }

        return self::SUCCESS;
    }
}
