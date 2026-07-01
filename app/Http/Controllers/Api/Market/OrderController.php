<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\CustomerNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * หลังบ้าน — จัดการคำสั่งซื้อ (scope ตามกลุ่ม) + จัดส่ง + เปลี่ยนสถานะ
 */
class OrderController extends Controller
{
    /** กลุ่มสถานะแบบ Shopee */
    private const GROUPS = [
        'to_pay'    => ['pending_payment', 'awaiting_confirm'],
        'to_ship'   => ['confirmed', 'processing'],
        'shipped'   => ['shipped', 'delivered'],
        'completed' => ['completed'],
        'return'    => ['refund_requested', 'refunded', 'cancelled'],
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $query = Order::query()
            ->with(['user:id,name', 'items', 'latestPayment', 'sellerGroup:id,name', 'shipment'])
            ->withCount('items');

        if (($scope = $user->sellerGroupScope()) !== null) $query->where('seller_group_id', $scope);
        elseif ($request->filled('group_id'))              $query->where('seller_group_id', $request->input('group_id'));

        if ($g = $request->input('status_group')) {
            $query->whereIn('status', self::GROUPS[$g] ?? []);
        }
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($q = trim((string) $request->input('q'))) {
            $query->where(fn ($s) => $s->where('order_no', 'like', "%{$q}%")->orWhere('shipping_name', 'like', "%{$q}%"));
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->input('per_page', 20))
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);
        return response()->json(
            $order->load(['user:id,name,email', 'items.product:id,slug', 'statusHistories.changedBy:id,name', 'payments', 'shipment', 'returnRequests'])
        );
    }

    /** บันทึกการจัดส่ง (เลขพัสดุ) → สถานะ shipped */
    public function ship(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);
        abort_unless(
            in_array($order->status, [Order::STATUS_CONFIRMED, Order::STATUS_PROCESSING], true),
            422,
            'ออเดอร์นี้ยังจัดส่งไม่ได้ (ต้องยืนยันการชำระเงินก่อน)',
        );

        $data = $request->validate([
            'carrier'     => ['nullable', 'string', 'max:100'],
            'tracking_no' => ['nullable', 'string', 'max:100'],
            'note'        => ['nullable', 'string', 'max:500'],
        ]);

        Shipment::updateOrCreate(
            ['order_id' => $order->id],
            [
                'carrier'     => $data['carrier'] ?? null,
                'tracking_no' => $data['tracking_no'] ?? null,
                'note'        => $data['note'] ?? null,
                'shipped_by'  => $request->user()->id,
                'shipped_at'  => now(),
                'status'      => 'shipped',
            ],
        );

        $order->update(['shipped_at' => now()]);
        $note = 'จัดส่งแล้ว' . (!empty($data['tracking_no']) ? " ({$data['tracking_no']})" : '');
        $order->changeStatus(Order::STATUS_SHIPPED, $request->user()->id, $note);

        // แจ้งเตือนลูกค้าทันทีที่จัดส่ง
        $fresh = $order->fresh(['shipment']);
        CustomerNotificationService::shipped($fresh);

        // ส่งข้อความแชทอัตโนมัติแจ้งเปลี่ยน/ยืนยันการจัดส่ง
        $conversation = Conversation::firstOrCreate(
            ['seller_group_id' => $order->seller_group_id, 'customer_id' => $order->user_id],
            ['last_message_at' => now(), 'is_read_by_seller' => true, 'is_read_by_customer' => false],
        );
        $carrier  = $data['carrier']     ?? null;
        $tracking = $data['tracking_no'] ?? null;
        $chatBody = "📦 ร้านค้าได้บันทึกข้อมูลการจัดส่งสำหรับคำสั่งซื้อ {$order->order_no} แล้ว";
        if ($carrier)  $chatBody .= "\nบริษัทขนส่ง: {$carrier}";
        if ($tracking) $chatBody .= "\nเลขพัสดุ: {$tracking}";
        if (!$carrier && !$tracking) $chatBody .= "\n(จัดส่งโดยผู้ขายโดยตรง ไม่มีเลขพัสดุ)";
        $chatBody .= "\n\nหากมีค่าใช้จ่ายเพิ่มเติมหรือข้อสงสัยเรื่องการจัดส่ง สามารถพูดคุยกับร้านค้าได้ที่นี่ หรือดูข้อมูลติดต่อที่หน้าร้านค้า";
        $conversation->messages()->create([
            'sender_type' => 'staff',
            'sender_id'   => $request->user()->id,
            'body'        => $chatBody,
            'is_read'     => false,
        ]);
        $conversation->update([
            'last_message_at'     => now(),
            'is_read_by_customer' => false,
            'is_read_by_seller'   => true,
        ]);

        return response()->json(['message' => 'บันทึกการจัดส่งแล้ว', 'order' => $fresh]);
    }

    /** เปลี่ยนสถานะทั่วไป (processing / cancelled) */
    public function setStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        $data = $request->validate([
            'status' => ['required', 'in:processing,cancelled'],
            'note'   => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['status'] === 'processing') {
            abort_unless($order->status === Order::STATUS_CONFIRMED, 422, 'เปลี่ยนเป็นกำลังเตรียมได้เฉพาะออเดอร์ที่ยืนยันแล้ว');
        }
        if ($data['status'] === 'cancelled') {
            abort_if(in_array($order->status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true), 422, 'ออเดอร์ที่จัดส่งแล้วยกเลิกไม่ได้');
            // คืนสต็อก
            foreach ($order->items as $item) {
                if ($item->product) $item->product->increment('stock_qty', $item->qty);
            }
        }

        $order->changeStatus($data['status'], $request->user()->id, $data['note'] ?? null);

        return response()->json(['message' => 'อัปเดตสถานะแล้ว', 'order' => $order->fresh()]);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');
        abort_unless($user->canActInGroup($order->seller_group_id), 403, 'ออเดอร์นี้อยู่นอกกลุ่มที่คุณดูแล');
    }
}
