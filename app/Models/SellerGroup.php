<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'districts'            => 'array',
        'is_active'            => 'boolean',
        'line_notify_enabled' => 'boolean',
    ];

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
}
