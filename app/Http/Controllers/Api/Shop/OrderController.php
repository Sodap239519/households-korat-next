<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnRequest;
use App\Services\LineNotifier;
use App\Services\SlipVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ออเดอร์ฝั่งลูกค้า — ดูรายการ/รายละเอียด + แจ้งชำระเงิน (แนบสลิป)
 */
class OrderController extends Controller
{
    /** กลุ่มสถานะแบบ Shopee สำหรับแท็บ */
    private const GROUPS = [
        'to_pay'    => ['pending_payment', 'awaiting_confirm'],
        'to_ship'   => ['confirmed', 'processing'],
        'to_receive' => ['shipped', 'delivered'],
        'completed' => ['completed'],
        'return'    => ['refund_requested', 'refunded', 'cancelled'],
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Order::where('user_id', $request->user()->id)
            ->with(['sellerGroup:id,name', 'items.product.images', 'latestPayment', 'shipment'])
            ->withCount('items');

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

        // ตรวจสลิปอัตโนมัติ (ถ้าตั้งค่าบริการไว้) + แจ้ง LINE กลุ่มผู้ขาย
        SlipVerifier::verify($payment);
        LineNotifier::paymentSubmitted($payment->fresh());

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

    /** ลูกค้าขอคืนสินค้า/คืนเงิน/เคลม */
    public function requestReturn(Request $request, string $orderNo): JsonResponse
    {
        $order = Order::where('order_no', $orderNo)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        abort_unless(
            in_array($order->status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true),
            422,
            'ออเดอร์นี้ยังขอคืน/เคลมไม่ได้',
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

        LineNotifier::returnRequested($return->fresh());

        return response()->json(['message' => 'ส่งคำขอแล้ว รอผู้ขายตรวจสอบ', 'order' => $order->fresh()], 201);
    }
}
