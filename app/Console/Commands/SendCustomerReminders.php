<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use App\Models\CustomerNotification;
use App\Models\Order;
use App\Services\CustomerNotificationService;
use Illuminate\Console\Command;

class SendCustomerReminders extends Command
{
    protected $signature   = 'shop:send-reminders';
    protected $description = 'ส่งการแจ้งเตือนลูกค้า: กดรับสินค้า + ตะกร้าค้าง';

    public function handle(): int
    {
        $this->remindShipping();
        $this->remindAbandonedCart();

        $this->info('ส่งการแจ้งเตือนลูกค้าเรียบร้อย');
        return 0;
    }

    /** แจ้งเตือนลูกค้าที่ยังไม่กดรับสินค้าหลังจัดส่งแล้วเกิน 3 วัน */
    private function remindShipping(): void
    {
        $orders = Order::where('status', Order::STATUS_SHIPPED)
            ->where('shipped_at', '<=', now()->subDays(3))
            ->get();

        foreach ($orders as $order) {
            $alreadySent = CustomerNotification::where('user_id', $order->user_id)
                ->where('type', 'shipping_reminder')
                ->whereJsonContains('data->order_id', $order->id)
                ->exists();

            if (!$alreadySent) {
                CustomerNotificationService::shippingReminder($order);
                $this->line("  ✓ แจ้งเตือนรับสินค้า #{$order->order_no} (user #{$order->user_id})");
            }
        }
    }

    /** แจ้งเตือนลูกค้าที่มีสินค้าค้างในตะกร้านานเกิน 3 วัน */
    private function remindAbandonedCart(): void
    {
        $cartUsers = CartItem::where('added_at', '<=', now()->subDays(3))
            ->with('product:id,name')
            ->get()
            ->groupBy('user_id');

        foreach ($cartUsers as $userId => $items) {
            // ไม่ส่งซ้ำถ้าส่งไปแล้วภายใน 3 วัน
            $recentSent = CustomerNotification::where('user_id', $userId)
                ->where('type', 'abandoned_cart')
                ->where('created_at', '>=', now()->subDays(3))
                ->exists();

            if ($recentSent) continue;

            $productNames = $items->pluck('product.name')->filter()->values()->all();
            if (!$productNames) continue;

            CustomerNotificationService::abandonedCart((int) $userId, $productNames);
            $this->line("  ✓ แจ้งเตือนตะกร้าค้าง user #{$userId} ({$items->count()} รายการ)");
        }
    }
}
