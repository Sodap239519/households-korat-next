<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\CustomerNotification;
use App\Models\FlashSaleEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\WishlistItem;

class CustomerNotificationService
{
    public static function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?array $data = null,
    ): CustomerNotification {
        return CustomerNotification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
            'data'    => $data,
        ]);
    }

    /**
     * แจ้งเตือนลูกค้าที่เพิ่มสินค้านี้ใน wishlist เมื่อราคาลดลง
     */
    public static function promotion(Product $product, float $oldPrice, float $newPrice): void
    {
        if ($newPrice >= $oldPrice) return;

        $diff = number_format($oldPrice - $newPrice, 0);
        $userIds = WishlistItem::where('product_id', $product->id)->pluck('user_id');

        foreach ($userIds as $userId) {
            self::create(
                userId:  $userId,
                type:    'promotion',
                title:   'สินค้าที่คุณชอบลดราคาแล้ว!',
                message: "{$product->name} ลดลง ฿{$diff} เหลือ ฿" . number_format($newPrice, 0),
                link:    "/shop/products/{$product->slug}",
                data:    [
                    'product_id'   => $product->id,
                    'product_slug' => $product->slug,
                    'old_price'    => $oldPrice,
                    'new_price'    => $newPrice,
                ],
            );
        }
    }

    /**
     * แจ้งเตือนทันทีเมื่อผู้ขายกดจัดส่งสินค้า
     */
    public static function shipped(Order $order): void
    {
        $order->loadMissing(['shipment', 'sellerGroup:id,name']);
        $tracking = $order->shipment?->tracking_no;
        $carrier  = $order->shipment?->carrier;
        $detail   = $carrier ? " ผ่าน {$carrier}" : '';
        $trackInfo = $tracking ? " เลขพัสดุ: {$tracking}" : '';

        self::create(
            userId:  $order->user_id,
            type:    'shipped',
            title:   'ออเดอร์ถูกจัดส่งแล้ว!',
            message: "ออเดอร์ #{$order->order_no}{$detail}{$trackInfo}",
            link:    "/shop/account/orders/{$order->order_no}",
            data:    [
                'order_id'    => $order->id,
                'order_no'    => $order->order_no,
                'tracking_no' => $tracking,
                'carrier'     => $carrier,
            ],
        );
    }

    /**
     * แจ้งเตือนกดรับสินค้าเมื่อจัดส่งไปนานแล้ว (Scheduler เรียก)
     */
    public static function shippingReminder(Order $order): void
    {
        self::create(
            userId:  $order->user_id,
            type:    'shipping_reminder',
            title:   'อย่าลืมกดยืนยันรับสินค้านะคะ!',
            message: "ออเดอร์ #{$order->order_no} จัดส่งมานานกว่า 3 วันแล้ว กดรับสินค้าเพื่อปิดออเดอร์",
            link:    "/shop/account/orders/{$order->order_no}",
            data:    ['order_id' => $order->id, 'order_no' => $order->order_no],
        );
    }

    /**
     * แจ้งเตือนลูกค้าที่มีสินค้าใน flash sale อยู่ในตะกร้า
     * เรียกเมื่อ event เปิดใช้งาน หรือเพิ่มสินค้าเข้า event ที่กำลังดำเนินการ
     *
     * @param  int[]  $productIds  รายการ product_id ที่เพิ่งเพิ่มเข้า flash sale
     */
    public static function cartFlashSale(FlashSaleEvent $event, array $productIds): void
    {
        if (empty($productIds)) return;

        // จับกลุ่มผู้ใช้ที่มีสินค้าเหล่านี้ในตะกร้า → แต่ละ user รับ notification เดียว
        $cartRows = CartItem::whereIn('product_id', $productIds)
            ->with('product:id,name,slug')
            ->get()
            ->groupBy('user_id');

        foreach ($cartRows as $userId => $rows) {
            $names = $rows->map(fn ($r) => $r->product?->name)->filter()->values();
            $first  = $names->first() ?? 'สินค้า';
            $more   = $names->count() > 1 ? " และอีก " . ($names->count() - 1) . " รายการ" : '';

            self::create(
                userId:  (int) $userId,
                type:    'flash_sale',
                title:   "Flash Sale! สินค้าในตะกร้าของคุณกำลังจัดโปร",
                message: "{$first}{$more} มีราคา Flash Sale แล้ว — ซื้อก่อนหมดเวลา!",
                link:    '/shop/cart',
                data:    [
                    'event_id'    => $event->id,
                    'event_title' => $event->title,
                    'ends_at'     => $event->ends_at?->toIso8601String(),
                    'product_ids' => $productIds,
                ],
            );
        }
    }

    /**
     * แจ้งเตือนตะกร้าค้าง (Scheduler เรียก)
     */
    public static function abandonedCart(int $userId, array $productNames): void
    {
        $first = $productNames[0] ?? 'สินค้า';
        $more  = count($productNames) > 1 ? ' และอีก ' . (count($productNames) - 1) . ' รายการ' : '';

        self::create(
            userId:  $userId,
            type:    'abandoned_cart',
            title:   'สินค้าในตะกร้ารอคุณอยู่!',
            message: "{$first}{$more} ยังรอการสั่งซื้ออยู่ กลับมาดูกันนะคะ",
            link:    '/shop/cart',
            data:    ['products' => array_values($productNames)],
        );
    }
}
