<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['อีเมลหรือรหัสผ่านไม่ถูกต้อง'],
            ]);
        }

        $request->session()->regenerate();

        $history = LoginHistory::create([
            'user_id'      => Auth::id(),
            'ip_address'   => $request->ip(),
            'user_agent'   => substr((string) $request->userAgent(), 0, 1000),
            'logged_in_at' => now(),
        ]);

        $request->session()->put('login_history_id', $history->id);

        return response()->json([
            'user' => Auth::user(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($historyId = $request->session()->get('login_history_id')) {
            LoginHistory::where('id', $historyId)
                ->whereNull('logged_out_at')
                ->update(['logged_out_at' => now()]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'ออกจากระบบสำเร็จ']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }
}
