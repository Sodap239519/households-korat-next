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
        abort_unless(
            $request->user() && $request->user()->isAdmin(),
            403,
            'ต้องเป็นผู้ดูแลระบบ (admin หรือ superadmin)',
        );
    }

    /** Roles a user is allowed to assign */
    private function assignableRoles(User $actor): array
    {
        // superadmin can assign any role; admin cannot create superadmin
        return $actor->isSuperAdmin()
            ? User::ROLES_ALL
            : [User::ROLE_STAFF, User::ROLE_AREA_STAFF, User::ROLE_ADMIN];
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

        // Only superadmin can edit a superadmin
        if ($user->isSuperAdmin() && !$request->user()->isSuperAdmin()) {
            abort(403, 'ต้องเป็น superadmin เท่านั้นที่แก้ไขบัญชี superadmin');
        }

        $assignable = $this->assignableRoles($request->user());

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'email'               => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'                => ['required', Rule::in($assignable)],
            'assigned_districts'  => ['nullable', 'array', 'max:' . User::MAX_ASSIGNED_DISTRICTS],
            'assigned_districts.*'=> ['string', 'max:100'],
            'seller_group_id'     => ['nullable', 'integer', 'exists:seller_groups,id'],
        ]);

        // Districts only meaningful for area_staff
        if (($validated['role'] ?? null) !== User::ROLE_AREA_STAFF) {
            $validated['assigned_districts'] = null;
        }
        // superadmin ไม่ผูกกับกลุ่มผู้ขาย
        if (($validated['role'] ?? null) === User::ROLE_SUPERADMIN) {
            $validated['seller_group_id'] = null;
        }

        $user->update($validated);

        return response()->json($user->fresh());
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($user->isSuperAdmin() && !$request->user()->isSuperAdmin()) {
            abort(403, 'ต้องเป็น superadmin เท่านั้นที่รีเซ็ตรหัสของ superadmin');
        }

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

        $assignable = $this->assignableRoles($request->user());

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'email'               => ['required', 'email', 'unique:users,email'],
            'role'                => ['required', Rule::in($assignable)],
            'assigned_districts'  => ['nullable', 'array', 'max:' . User::MAX_ASSIGNED_DISTRICTS],
            'assigned_districts.*'=> ['string', 'max:100'],
            'seller_group_id'     => ['nullable', 'integer', 'exists:seller_groups,id'],
            'password'            => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validated['role'] !== User::ROLE_AREA_STAFF) {
            $validated['assigned_districts'] = null;
        }
        if ($validated['role'] === User::ROLE_SUPERADMIN) {
            $validated['seller_group_id'] = null;
        }

        $validated['password']    = Hash::make($validated['password']);
        $validated['is_approved'] = true;
        $validated['approved_at'] = now();
        $validated['approved_by'] = $request->user()->id;
        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'ลบบัญชีตัวเองไม่ได้'], 422);
        }
        if ($user->isSuperAdmin() && !$request->user()->isSuperAdmin()) {
            abort(403, 'ต้องเป็น superadmin เท่านั้นที่ลบบัญชี superadmin');
        }

        $user->delete();

        return response()->json(['message' => 'ลบผู้ใช้สำเร็จ']);
    }
}
