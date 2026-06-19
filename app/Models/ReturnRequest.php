<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use HasFactory;

    public const TYPE_RETURN = 'return';
    public const TYPE_REFUND = 'refund';
    public const TYPE_CLAIM  = 'claim';

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_REFUNDED  = 'refunded';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'type',
        'reason',
        'description',
        'images',
        'status',
        'admin_response',
        'handled_by',
        'handled_at',
        'refund_amount',
    ];

    protected $casts = [
        'images'        => 'array',
        'handled_at'    => 'datetime',
        'refund_amount' => 'decimal:2',
    ];

    protected $appends = ['image_urls'];

    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($p) => asset('storage/' . $p))
            ->all();
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
