<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    private function authorize(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->isAdmin(),
            403,
            'ต้องเป็นผู้ดูแลระบบเท่านั้น',
        );
    }

    /** Lightweight counts for the bell badge */
    public function counts(Request $request): JsonResponse
    {
        $this->authorize($request);

        return response()->json([
            'unread_total'  => AdminNotification::whereNull('read_at')->count(),
            'pending_users' => User::where('is_approved', false)->count(),
        ]);
    }

    /** Paginated list for the bell dropdown / page */
    public function list(Request $request): JsonResponse
    {
        $this->authorize($request);

        $query = AdminNotification::with('actor:id,name,email')->orderByDesc('id');
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    public function markRead(Request $request, AdminNotification $notification): JsonResponse
    {
        $this->authorize($request);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }
        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->authorize($request);

        AdminNotification::whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    /** Pending users listing (for the inline approve UI) */
    public function pendingUsers(Request $request): JsonResponse
    {
        $this->authorize($request);

        $users = User::where('is_approved', false)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($users);
    }

    public function approve(Request $request, User $user): JsonResponse
    {
        $this->authorize($request);

        $user->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        // Mark related user_registered notifications as read
        AdminNotification::where('type', 'user_registered')
            ->whereNull('read_at')
            ->whereJsonContains('meta->user_id', $user->id)
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'อนุมัติผู้ใช้แล้ว',
            'user'    => $user->fresh(),
        ]);
    }

    public function reject(Request $request, User $user): JsonResponse
    {
        $this->authorize($request);

        if ($user->is_approved) {
            return response()->json(['message' => 'ผู้ใช้นี้ได้รับอนุมัติแล้ว ไม่สามารถปฏิเสธได้'], 422);
        }

        // Resolve any related notifications first
        AdminNotification::where('type', 'user_registered')
            ->whereNull('read_at')
            ->whereJsonContains('meta->user_id', $user->id)
            ->update(['read_at' => now()]);

        $user->delete();

        return response()->json(['message' => 'ปฏิเสธและลบบัญชีแล้ว']);
    }
}
