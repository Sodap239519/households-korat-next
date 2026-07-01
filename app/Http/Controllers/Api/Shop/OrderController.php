<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnRequest;
use App\Services\AdminNotificationService;
use App\Services\LineNotifier;
use App\Services\SlipVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ออเดอร์ฝั่งลูกค้า — ดูรายการ/รายละเอียด + แจ้งชำระเงิน (แนบสลิป)
 */
class OrderController extends Controller
{
    /** กลุ่มสถานะแบบ Shopee สำหรับแท็บ */
    private const GROUPS = [
        'to_pay'     => ['pending_payment', 'awaiting_confirm'],
        'to_ship'    => ['confirmed', 'processing'],
        'to_receive' => ['shipped', 'delivered'],
        'completed'  => ['completed'],
        'return'     => ['refund_requested', 'refunded', 'cancelled'],
        'history'    => ['completed', 'refund_requested', 'refunded', 'cancelled'],
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Order::where('user_id', $request->user()->id)
            ->with(['sellerGroup:id,name', 'items.product.images', 'latestPayment', 'shipment'])
            ->withCount(['items', 'reviews']);

        if ($g = $request->input('status_group')) {
            $query->whereIn('status', self::GROUPS[$g] ?? []);
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->input('per_page', 15))
        );
    }

    public function show(Request $request, string $orderNo): JsonResponse
    {
        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->with([
                'sellerGroup',
                'items.product:id,slug',
                'statusHistories',
                'payments',
                'shipment',
                'returnRequests',
                'reviews:id,order_id,product_id',
            ])
            ->firstOrFail();

        return response()->json($order);
    }

    /** ลูกค้าแนบสลิปแจ้งชำระเงิน */
    public function submitPayment(Request $request, string $orderNo): JsonResponse
    {
        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless(
            in_array($order->status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_AWAITING_CONFIRM], true),
            422,
            'ออเดอร์นี้ไม่อยู่ในสถานะที่แจ้งชำระเงินได้',
        );

        $data = $request->validate([
            'amount'   => ['required', 'numeric', 'min:0'],
            'bank_ref' => ['nullable', 'string', 'max:255'],
            'paid_at'  => ['nullable', 'date'],
            'slip'     => ['required', 'image', 'max:4096'],
        ], [], ['slip' => 'สลิปโอนเงิน']);

        $path = $request->file('slip')->store("slips/{$order->id}", 'public');

        $payment = $order->payments()->create([
            'amount'   => $data['amount'],
            'method'   => 'bank_transfer',
            'slip_path' => $path,
            'bank_ref' => $data['bank_ref'] ?? null,
            'paid_at'  => $data['paid_at'] ?? now(),
            'status'   => Payment::STATUS_PENDING,
        ]);

        $order->changeStatus(Order::STATUS_AWAITING_CONFIRM, $request->user()->id, 'ลูกค้าแจ้งชำระเงิน');

        // ตรวจสลิปอัตโนมัติ (ถ้าตั้งค่าบริการไว้) + แจ้ง LINE + admin notification
        SlipVerifier::verify($payment);
        $freshPayment = $payment->fresh();
        LineNotifier::paymentSubmitted($freshPayment);
        AdminNotificationService::paymentSubmitted($freshPayment);

        return response()->json([
            'message' => 'แจ้งชำระเงินเรียบร้อย รอผู้ขายยืนยัน',
            'payment' => $payment->fresh(),
            'order'   => $order->fresh(),
        ], 201);
    }

    /** ลูกค้ายืนยันได้รับสินค้าแล้ว → ปิดออเดอร์ (สำเร็จ) */
    public function receive(Request $request, string $orderNo): JsonResponse
    {
        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless(
            in_array($order->status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED], true),
            422,
            'ออเดอร์นี้ยังกดรับสินค้าไม่ได้',
        );

        $order->update(['delivered_at' => now()]);
        $order->changeStatus(Order::STATUS_COMPLETED, $request->user()->id, 'ลูกค้ายืนยันรับสินค้าแล้ว');

        return response()->json(['message' => 'ยืนยันรับสินค้าแล้ว ขอบคุณค่ะ', 'order' => $order->fresh()]);
    }

    /** ลูกค้ายกเลิกออเดอร์ (เฉพาะสถานะ pending_payment เท่านั้น) */
    public function cancel(Request $request, string $orderNo): JsonResponse
    {
        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->with('items.product')
            ->firstOrFail();

        abort_unless(
            $order->status === Order::STATUS_PENDING_PAYMENT,
            422,
            'ยกเลิกได้เฉพาะออเดอร์ที่รอชำระเงินเท่านั้น',
        );

        // คืนสต็อกสินค้าทุกรายการ
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock_qty', $item->qty);
            }
        }

        $order->changeStatus(Order::STATUS_CANCELLED, $request->user()->id, 'ลูกค้ายกเลิกออเดอร์');

        return response()->json(['message' => 'ยกเลิกออเดอร์แล้ว']);
    }

    /** ลูกค้าขอคืนสินค้า/คืนเงิน/เคลม */
    public function requestReturn(Request $request, string $orderNo): JsonResponse
    {
        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless(
            in_array($order->status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED], true),
            422,
            'ขอคืน/เคลมได้เฉพาะออเดอร์ที่จัดส่งแล้วและยังไม่ได้กดยืนยันรับสินค้า',
        );

        $data = $request->validate([
            'type'        => ['required', 'in:return,refund,claim'],
            'reason'      => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'images'      => ['nullable', 'array', 'max:5'],
            'images.*'    => ['image', 'max:4096'],
        ], [], ['reason' => 'เหตุผล']);

        $paths = [];
        foreach ($request->file('images', []) as $file) {
            $paths[] = $file->store("returns/{$order->id}", 'public');
        }

        $return = $order->returnRequests()->create([
            'user_id'     => $request->user()->id,
            'type'        => $data['type'],
            'reason'      => $data['reason'],
            'description' => $data['description'] ?? null,
            'images'      => $paths ?: null,
            'status'      => ReturnRequest::STATUS_REQUESTED,
        ]);

        $order->changeStatus(Order::STATUS_REFUND_REQUESTED, $request->user()->id, "คำขอ{$data['type']}: {$data['reason']}");

        $freshReturn = $return->fresh();
        LineNotifier::returnRequested($freshReturn);
        AdminNotificationService::returnRequested($freshReturn);

        return response()->json(['message' => 'ส่งคำขอแล้ว รอผู้ขายตรวจสอบ', 'order' => $order->fresh()], 201);
    }

    /** สรุปยอดการซื้อ
     *  order_count = ทุก order ยกเว้น cancelled/refunded
     *  total_spent / total_saved = เฉพาะ completed + delivered (ชำระเสร็จ + รับของแล้ว)
     */
    public function summary(Request $request): JsonResponse
    {
        $uid = $request->user()->id;

        $settled  = ['delivered', 'completed'];
        $counted  = ['completed', 'refunded'];

        $countRow = Order::where('user_id', $uid)
            ->whereIn('status', $counted)
            ->selectRaw('COUNT(*) as order_count')
            ->first();

        $spentRow = Order::where('user_id', $uid)
            ->whereIn('status', $settled)
            ->selectRaw('COALESCE(SUM(total),0) as total_spent, COALESCE(SUM(discount),0) as total_saved')
            ->first();

        // นับ order ที่มี item ที่ยังไม่ได้รีวิว (status delivered/completed)
        $toReview = (int) DB::table('orders')
            ->where('orders.user_id', $uid)
            ->whereIn('orders.status', $settled)
            ->whereExists(function ($sub) use ($uid) {
                $sub->select(DB::raw(1))
                    ->from('order_items')
                    ->join('products', 'products.id', '=', 'order_items.product_id')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->whereNotExists(function ($r) use ($uid) {
                        $r->select(DB::raw(1))
                          ->from('product_reviews')
                          ->whereColumn('product_reviews.product_id', 'order_items.product_id')
                          ->whereColumn('product_reviews.order_id', 'order_items.order_id')
                          ->where('product_reviews.user_id', $uid);
                    });
            })
            ->count();

        return response()->json([
            'order_count' => (int)   ($countRow->order_count ?? 0),
            'total_spent' => (float) ($spentRow->total_spent  ?? 0),
            'total_saved' => (float) ($spentRow->total_saved  ?? 0),
            'to_review'   => $toReview,
        ]);
    }

    /** รายการคำสั่งซื้อที่มีส่วนลด (เฉพาะ completed) */
    public function savings(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->where('discount', '>', 0)
            ->with(['sellerGroup:id,name,slug', 'items'])
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($orders);
    }

    /** นับการแจ้งเตือนสำหรับลูกค้า (สลิปยืนยันแล้ว + จัดส่งแล้ว) */
    public function notificationCount(Request $request): JsonResponse
    {
        $count = Order::where('user_id', $request->user()->id)
            ->whereIn('status', [
                Order::STATUS_PENDING_PAYMENT,
                Order::STATUS_AWAITING_CONFIRM,
                Order::STATUS_CONFIRMED,
                Order::STATUS_PROCESSING,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
            ])
            ->count();

        return response()->json(['count' => $count]);
    }
}
