<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SellerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'contact_phone',
        'contact_address',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'promptpay_id',
        'logo_path',
        'line_target_id',
        'line_notify_enabled',
        'districts',
        'is_active',
        'lat',
        'lng',
        'map_label',
        'shop_status',
        'suspended_until',
        'ban_reason',
        'banned_by',
        'banned_at',
    ];

    public const SHOP_STATUS_ACTIVE    = 'active';
    public const SHOP_STATUS_SUSPENDED = 'suspended';
    public const SHOP_STATUS_BANNED    = 'banned';

    protected $casts = [
        'districts'            => 'array',
        'is_active'            => 'boolean',
        'line_notify_enabled'  => 'boolean',
        'suspended_until'      => 'datetime',
        'banned_at'            => 'datetime',
    ];

    public function isEffectivelyActive(): bool
    {
        if ($this->shop_status === self::SHOP_STATUS_BANNED) return false;
        if ($this->shop_status === self::SHOP_STATUS_SUSPENDED) {
            // หมดเวลาระงับ = กลับ active อัตโนมัติ
            return $this->suspended_until && $this->suspended_until->isPast();
        }
        return true;
    }

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /** กลุ่มนี้พร้อมรับแจ้งเตือน LINE หรือไม่ */
    public function canReceiveLineNotify(): bool
    {
        return $this->line_notify_enabled && !empty($this->line_target_id);
    }

    public function members()
    {
        return $this->hasMany(User::class, 'seller_group_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function shippingOptions()
    {
        return $this->hasMany(SellerShippingOption::class)->orderBy('sort_order')->orderBy('fee');
    }

    public function activeShippingOptions()
    {
        return $this->shippingOptions()->where('is_active', true);
    }
}
