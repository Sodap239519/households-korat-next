<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Role values, in increasing privilege order */
    public const ROLE_STAFF       = 'staff';
    public const ROLE_AREA_STAFF  = 'area_staff';
    public const ROLE_ADMIN       = 'admin';
    public const ROLE_SUPERADMIN  = 'superadmin';
    public const ROLES_ALL        = [self::ROLE_STAFF, self::ROLE_AREA_STAFF, self::ROLE_ADMIN, self::ROLE_SUPERADMIN];

    /** Maximum number of districts an area_staff user can be assigned. */
    public const MAX_ASSIGNED_DISTRICTS = 4;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'assigned_districts',
        'is_approved',
        'approved_at',
        'approved_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'is_approved'         => 'boolean',
            'approved_at'         => 'datetime',
            'assigned_districts'  => 'array',
        ];
    }

    // ===== Role helpers =====

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPERADMIN], true);
    }

    public function isAreaStaff(): bool
    {
        return in_array(
            $this->role,
            [self::ROLE_AREA_STAFF, self::ROLE_ADMIN, self::ROLE_SUPERADMIN],
            true,
        );
    }

    public function isStaff(): bool
    {
        return in_array($this->role, self::ROLES_ALL, true);
    }

    /** Districts this user is allowed to act on (admins implicitly cover all). */
    public function districtsScope(): ?array
    {
        if ($this->isAdmin()) return null;        // null = no restriction
        if ($this->role === self::ROLE_AREA_STAFF) {
            return $this->assigned_districts ?? [];
        }
        return [];                                 // empty = nothing allowed
    }

    /** Can this user create / modify allocations / followups in $district? */
    public function canActInDistrict(?string $district): bool
    {
        if ($this->isAdmin()) return true;
        if ($this->role !== self::ROLE_AREA_STAFF) return false;
        if (!$district) return false;
        return in_array($district, $this->assigned_districts ?? [], true);
    }

    public function canManageQuotas(): bool   { return $this->isAdmin(); }
    public function canManageUsers(): bool    { return $this->isAdmin(); }
    public function canCreateAllocation(): bool { return $this->isAreaStaff(); }
    public function canCreateFollowup(): bool   { return $this->isAreaStaff(); }
    public function canEditHouseholds(): bool   { return $this->isStaff(); }

    public function approver()
    {
        return $this->belongsTo(self::class, 'approved_by');
    }
}
