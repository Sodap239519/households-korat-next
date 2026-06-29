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

    /** Customer role — ลูกค้าหน้าร้าน (แยกจากเจ้าหน้าที่ ไม่อยู่ใน ROLES_ALL) */
    public const ROLE_CUSTOMER    = 'customer';

    /** Maximum number of districts an area_staff user can be assigned. */
    public const MAX_ASSIGNED_DISTRICTS = 4;

    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'password',
        'role',
        'assigned_districts',
        'seller_group_id',
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

    // ===== Marketplace helpers =====

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    /** สมาชิกเจ้าหน้าที่ที่ทำงานหลังบ้านตลาด (สังกัดกลุ่ม หรือเป็น admin) */
    public function isMarketStaff(): bool
    {
        return $this->isAdmin() || ($this->isStaff() && !is_null($this->seller_group_id));
    }

    /** กลุ่มผู้ขายที่ผู้ใช้สังกัด */
    public function sellerGroup()
    {
        return $this->belongsTo(SellerGroup::class, 'seller_group_id');
    }

    /** กลุ่มที่ผู้ใช้จัดการได้ (admin = null คือไม่จำกัด, เจ้าหน้าที่ = กลุ่มตัวเอง) */
    public function sellerGroupScope(): ?int
    {
        if ($this->isAdmin()) return null;        // null = ไม่จำกัด เห็นทุกกลุ่ม
        return $this->seller_group_id;            // จำกัดเฉพาะกลุ่มตัวเอง
    }

    /** ผู้ใช้จัดการข้อมูลตลาดของกลุ่มนี้ได้หรือไม่ */
    public function canActInGroup(?int $groupId): bool
    {
        if ($this->isAdmin()) return true;
        if (!$this->isMarketStaff()) return false;
        if (!$groupId) return false;
        return (int) $this->seller_group_id === (int) $groupId;
    }

    public function canManageSellerGroups(): bool { return $this->isAdmin(); }

    public function approver()
    {
        return $this->belongsTo(self::class, 'approved_by');
    }
}
