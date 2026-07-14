<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING_PAYMENT  = 'pending_payment';
    public const STATUS_AWAITING_CONFIRM = 'awaiting_confirm';
    public const STATUS_CONFIRMED        = 'confirmed';
    public const STATUS_PROCESSING       = 'processing';
    public const STATUS_SHIPPED          = 'shipped';
    public const STATUS_DELIVERED        = 'delivered';
    public const STATUS_COMPLETED        = 'completed';
    public const STATUS_CANCELLED        = 'cancelled';
    public const STATUS_REFUND_REQUESTED = 'refund_requested';
    public const STATUS_REFUNDED         = 'refunded';

    protected $fillable = [
        'order_no',
        'user_id',
        'seller_group_id',
        'seller_user_id',
        'status',
        'subtotal',
        'shipping_fee',
        'shipping_method',
        'payment_method',
        'discount',
        'total',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_sub_district',
        'shipping_district',
        'shipping_province',
        'shipping_zipcode',
        'shipping_carrier',
        'shipping_option_id',
        'shipping_note',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount'     => 'decimal:2',
        'total'        => 'decimal:2',
        'shipped_at'   => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sellerGroup()
    {
        return $this->belongsTo(SellerGroup::class);
    }

    public function sellerUser()
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipment()
    {
        // ปกติ 1 ออเดอร์มี 1 การจัดส่ง — ใช้ hasOne ธรรมดา (เลี่ยง latestOfMany ที่ทำให้ updateOrCreate ambiguous)
        return $this->hasOne(Shipment::class)->latest('id');
    }

    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    /** บันทึกการเปลี่ยนสถานะพร้อม audit trail */
    public function changeStatus(string $status, ?int $userId = null, ?string $note = null): void
    {
        $this->update(['status' => $status]);
        $this->statusHistories()->create([
            'status'     => $status,
            'note'       => $note,
            'changed_by' => $userId,
            'created_at' => now(),
        ]);
    }
}
