<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Self-registration: only staff or area_staff allowed (admin/superadmin must be created via admin panel)
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:120'],
            'email'                 => ['required', 'email', 'unique:users,email'],
            'role'                  => ['required', Rule::in([User::ROLE_STAFF, User::ROLE_AREA_STAFF])],
            'assigned_districts'    => ['nullable', 'array', 'max:' . User::MAX_ASSIGNED_DISTRICTS],
            'assigned_districts.*'  => ['string', 'max:100'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Districts only meaningful for area_staff
        if ($validated['role'] !== User::ROLE_AREA_STAFF) {
            $validated['assigned_districts'] = null;
        } else {
            // area_staff must specify at least 1 district
            if (empty($validated['assigned_districts'])) {
                return response()->json([
                    'message' => 'เจ้าหน้าที่ประจำพื้นที่ต้องเลือกอำเภอที่ดูแลอย่างน้อย 1 อำเภอ',
                ], 422);
            }
        }

        $user = User::create([
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'password'           => Hash::make($validated['password']),
            'role'               => $validated['role'],
            'assigned_districts' => $validated['assigned_districts'],
            'is_approved'        => false,
        ]);

        AdminNotificationService::userRegistered($user);

        return response()->json([
            'message' => 'สมัครสมาชิกสำเร็จ — กรุณารอผู้ดูแลระบบยืนยันสิทธิ์ก่อนเข้าใช้งาน',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ], 201);
    }
}
