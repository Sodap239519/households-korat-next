<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerShippingOption extends Model
{
    protected $fillable = [
        'seller_group_id',
        'name',
        'carrier',
        'fee',
        'days_min',
        'days_max',
        'is_default',
        'is_active',
        'sort_order',
        'allowed_payment_methods',
    ];

    protected $casts = [
        'fee'                     => 'decimal:2',
        'days_min'                => 'integer',
        'days_max'                => 'integer',
        'is_default'              => 'boolean',
        'is_active'               => 'boolean',
        'sort_order'              => 'integer',
        'allowed_payment_methods' => 'array',
    ];

    /** ค่าเริ่มต้น: รองรับเฉพาะโอนเงินในระบบ */
    public function getAllowedPaymentMethodsAttribute($value): array
    {
        return $value ? json_decode($value, true) : ['online'];
    }

    public function sellerGroup()
    {
        return $this->belongsTo(SellerGroup::class);
    }

    /** label แสดงระยะเวลาจัดส่ง */
    public function getDaysLabelAttribute(): string
    {
        return $this->days_min === $this->days_max
            ? "{$this->days_min} วัน"
            : "{$this->days_min}–{$this->days_max} วัน";
    }
}
