<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * หลังบ้าน — จัดการคำขอคืนสินค้า/คืนเงิน/เคลม (scope ตามกลุ่ม)
 */
class ReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');

        $query = ReturnRequest::query()
            ->with(['order:id,order_no,total,seller_group_id', 'user:id,name'])
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

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->input('per_page', 20))
        );
    }

    /** ดำเนินการคำขอ: อนุมัติ / ปฏิเสธ / คืนเงินแล้ว */
    public function resolve(Request $request, ReturnRequest $returnRequest): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isMarketStaff(), 403, 'ไม่มีสิทธิ์เข้าถึงระบบตลาด');
        $order = $returnRequest->order;
        abort_unless($user->canActInGroup($order->seller_group_id), 403, 'คำขอนี้อยู่นอกกลุ่มที่คุณดูแล');

        $data = $request->validate([
            'action'         => ['required', 'in:approve,reject,refunded'],
            'admin_response' => ['nullable', 'string', 'max:1000'],
            'refund_amount'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $map = [
            'approve'  => ReturnRequest::STATUS_APPROVED,
            'reject'   => ReturnRequest::STATUS_REJECTED,
            'refunded' => ReturnRequest::STATUS_REFUNDED,
        ];

        $returnRequest->update([
            'status'         => $map[$data['action']],
            'admin_response' => $data['admin_response'] ?? null,
            'refund_amount'  => $data['refund_amount'] ?? null,
            'handled_by'     => $user->id,
            'handled_at'     => now(),
        ]);

        // ปรับสถานะออเดอร์ตามผล
        if ($data['action'] === 'reject') {
            $order->changeStatus(Order::STATUS_COMPLETED, $user->id, 'ปฏิเสธคำขอคืน/เคลม');
        } elseif ($data['action'] === 'refunded') {
            $order->changeStatus(Order::STATUS_REFUNDED, $user->id, 'คืนเงินแล้ว');
        }

        return response()->json(['message' => 'ดำเนินการแล้ว', 'return' => $returnRequest->fresh(), 'order' => $order->fresh()]);
    }
}
