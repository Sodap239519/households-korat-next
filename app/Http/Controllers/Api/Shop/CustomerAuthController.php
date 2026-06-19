<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * สมัคร/จัดการบัญชีลูกค้าหน้าร้าน (role = customer, อนุมัติอัตโนมัติ)
 */
class CustomerAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [], [
            'name'     => 'ชื่อ',
            'email'    => 'อีเมล',
            'password' => 'รหัสผ่าน',
        ]);

        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'role'        => User::ROLE_CUSTOMER,
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        // ล็อกอินทันทีหลังสมัคร
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => Auth::user()], 201);
    }
}
