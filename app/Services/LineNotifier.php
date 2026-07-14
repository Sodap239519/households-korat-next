<?php

namespace App\Services;

use App\Jobs\SendLineNotification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnRequest;
use App\Models\SellerGroup;

/**
 * รวมจุดส่งแจ้งเตือน LINE ไปยังกลุ่มผู้ขาย
 * ใช้ OA กลาง (token ใน config/services.line) + line_target_id ของแต่ละกลุ่ม
 * ทุก method ปลอดภัยเมื่อยังไม่ตั้งค่า — จะไม่ส่งและไม่ error
 */
class LineNotifier
{
    /** ส่งข้อความ text ไปยังกลุ่ม (ถ้ากลุ่มเปิดรับและมี target) */
    public static function toGroup(?SellerGroup $group, string $message): void
    {
        if (!$group || !$group->canReceiveLineNotify()) {
            return;
        }
        SendLineNotification::dispatch($group->line_target_id, $message);
    }

    /** แจ้งคำสั่งซื้อใหม่ */
    public static function orderPlaced(Order $order): void
    {
        $order->loadMissing('sellerGroup', 'items');
        $items = $order->items
            ->map(fn ($i) => "• {$i->product_name} × {$i->qty}")
            ->implode("\n");

        $msg = "🛒 มีคำสั่งซื้อใหม่\n"
            . "เลขที่: {$order->order_no}\n"
            . "ลูกค้า: {$order->shipping_name}\n"
            . "ยอดรวม: ฿" . number_format((float) $order->total, 2) . "\n"
            . "รายการ:\n{$items}\n"
            . "สถานะ: รอชำระเงิน";

        self::toGroup($order->sellerGroup, $msg);
    }

    /** แจ้งลูกค้าแนบสลิป/แจ้งชำระเงิน */
    public static function paymentSubmitted(Payment $payment): void
    {
        $payment->loadMissing('order.sellerGroup');
        $order = $payment->order;

        $msg = "💸 มีการแจ้งชำระเงิน\n"
            . "เลขที่: {$order->order_no}\n"
            . "จำนวน: ฿" . number_format((float) $payment->amount, 2) . "\n"
            . ($payment->bank_ref ? "อ้างอิง: {$payment->bank_ref}\n" : '')
            . "กรุณาตรวจสอบและยืนยันการชำระเงินในระบบหลังบ้าน";

        self::toGroup($order->sellerGroup, $msg);
    }

    /** แจ้งคำขอคืนเงิน/เคลมสินค้า */
    public static function returnRequested(ReturnRequest $return): void
    {
        $return->loadMissing('order.sellerGroup');
        $order = $return->order;

        $typeLabel = [
            ReturnRequest::TYPE_RETURN => 'ขอคืนสินค้า',
            ReturnRequest::TYPE_REFUND => 'ขอคืนเงิน',
            ReturnRequest::TYPE_CLAIM  => 'เคลมสินค้า',
        ][$return->type] ?? $return->type;

        $msg = "⚠️ มีคำขอ{$typeLabel}\n"
            . "เลขที่ออเดอร์: {$order->order_no}\n"
            . "เหตุผล: {$return->reason}\n"
            . ($return->description ? "รายละเอียด: {$return->description}\n" : '')
            . "กรุณาตรวจสอบในระบบหลังบ้าน";

        self::toGroup($order->sellerGroup, $msg);
    }
}
