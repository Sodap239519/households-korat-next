<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\CustomerNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerNotificationController extends Controller
{
    /** รายการแจ้งเตือนของลูกค้า (paginated, ล่าสุดก่อน) */
    public function index(Request $request): JsonResponse
    {
        $notifs = CustomerNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifs);
    }

    /** จำนวนที่ยังไม่อ่าน */
    public function count(Request $request): JsonResponse
    {
        $count = CustomerNotification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /** ทำเครื่องหมายอ่านแล้ว (รายการเดียว) */
    public function markRead(Request $request, CustomerNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    /** ทำเครื่องหมายอ่านทั้งหมด */
    public function markAllRead(Request $request): JsonResponse
    {
        CustomerNotification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
