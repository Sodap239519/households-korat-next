<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED  = 'rejected';

    protected $fillable = [
        'order_id',
        'amount',
        'method',
        'slip_path',
        'bank_ref',
        'paid_at',
        'status',
        'verify_status',
        'verify_detail',
        'confirmed_by',
        'confirmed_at',
        'reject_reason',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'paid_at'       => 'datetime',
        'confirmed_at'  => 'datetime',
        'verify_detail' => 'array',
    ];

    protected $appends = ['slip_url'];

    public function getSlipUrlAttribute(): ?string
    {
        return $this->slip_path ? asset('storage/' . $this->slip_path) : null;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
