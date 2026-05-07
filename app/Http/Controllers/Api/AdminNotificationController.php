<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    private function authorize(Request $request): void
    {
        abort_unless(
            $request->user() && $request->user()->isSuperAdmin(),
            403,
            'ต้องเป็น superadmin เท่านั้น'
        );
    }

    /** Lightweight count for the bell badge */
    public function counts(Request $request): JsonResponse
    {
        $this->authorize($request);

        return response()->json([
            'pending_users' => User::where('is_approved', false)->count(),
        ]);
    }

    /** List recent pending users for the dropdown / page */
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

        $user->delete();

        return response()->json(['message' => 'ปฏิเสธและลบบัญชีแล้ว']);
    }
}
