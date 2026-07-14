<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * หลังบ้าน — ยืนยัน/ปฏิเสธการชำระเงิน (ดูสลิป + ผลตรวจสลิปอัตโนมัติ)
 */
class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $query = Payment::query()
            ->with([
                'order:id,order_no,total,status,seller_group_id,user_id,shipping_name',
                'order.user:id,name',
                'order.sellerGroup:id,bank_account_name,promptpay_id',
            ])
            ->whereHas('order', function ($q) use ($user, $request) {
                if (($personal = $user->sellerPersonalScope()) !== null) {
                    $q->whereHas('items.product', fn ($p) => $p->where('seller_user_id', $personal));
                } elseif ($scope = $user->sellerGroupScope()) {
                    $q->where('seller_group_id', $scope);
                } elseif ($request->filled('group_id')) {
                    $q->where('seller_group_id', $request->input('group_id'));
                }
            });

        if ($request->filled('status')) $query->where('status', $request->input('status'));
        else $query->where('status', Payment::STATUS_PENDING); // ค่าเริ่มต้น: ที่รอยืนยัน

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->input('per_page', 20))
        );
    }

    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        $order = $this->authorizePayment($request, $payment);

        abort_unless($payment->status === Payment::STATUS_PENDING, 422, 'การชำระเงินนี้ถูกดำเนินการไปแล้ว');

        $payment->update([
            'status'       => Payment::STATUS_CONFIRMED,
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        $order->changeStatus(Order::STATUS_CONFIRMED, $request->user()->id, 'ยืนยันการชำระเงินแล้ว');

        return response()->json(['message' => 'ยืนยันการชำระเงินแล้ว', 'order' => $order->fresh()]);
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        $order = $this->authorizePayment($request, $payment);

        abort_unless($payment->status === Payment::STATUS_PENDING, 422, 'การชำระเงินนี้ถูกดำเนินการไปแล้ว');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [], ['reason' => 'เหตุผล']);

        $payment->update([
            'status'        => Payment::STATUS_REJECTED,
            'reject_reason' => $data['reason'],
            'confirmed_by'  => $request->user()->id,
            'confirmed_at'  => now(),
        ]);

        // ตีกลับให้ลูกค้าแจ้งชำระใหม่
        $order->changeStatus(Order::STATUS_PENDING_PAYMENT, $request->user()->id, 'ปฏิเสธสลิป: ' . $data['reason']);

        return response()->json(['message' => 'ปฏิเสธการชำระเงินแล้ว', 'order' => $order->fresh()]);
    }

    private function authorizePayment(Request $request, Payment $payment): Order
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');
        $order = $payment->order;
        abort_unless($user->canActInGroup($order->seller_group_id), 403, 'การชำระเงินนี้อยู่นอกกลุ่มที่คุณดูแล');
        return $order;
    }
}
