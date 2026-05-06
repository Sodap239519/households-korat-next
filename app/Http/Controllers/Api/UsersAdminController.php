<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersAdminController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user() && $request->user()->isSuperAdmin(), 403, 'ต้องเป็น superadmin เท่านั้น');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        return response()->json(
            $query->orderBy('id')->paginate($request->input('per_page', 20))
        );
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);
        return response()->json($user);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => ['required', Rule::in(['superadmin', 'staff'])],
        ]);

        $user->update($validated);

        return response()->json($user->fresh());
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        $request->validate([
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return response()->json(['message' => 'รีเซ็ตรหัสผ่านสำเร็จ']);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'role'     => ['required', Rule::in(['superadmin', 'staff'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'ลบบัญชีตัวเองไม่ได้'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'ลบผู้ใช้สำเร็จ']);
    }
}
