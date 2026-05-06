<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginHistoryController extends Controller
{
    public function mine(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $history = LoginHistory::where('user_id', $userId)
            ->orderByDesc('logged_in_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($history);
    }

    public function forUser(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'ต้องเป็น superadmin เท่านั้น');

        $history = LoginHistory::with('user:id,name,email')
            ->where('user_id', $user->id)
            ->orderByDesc('logged_in_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($history);
    }
}
