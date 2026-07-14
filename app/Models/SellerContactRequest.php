<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerContactRequest extends Model
{
    protected $fillable = [
        'seller_user_id',
        'seller_group_id',
        'topic',
        'detail',
        'status',
        'handled_by',
        'admin_note',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public const TOPICS = [
        'change_zone'       => 'ขอเปลี่ยนแปลงโซนการขาย',
        'change_categories' => 'ขอเปลี่ยนแปลงประเภทสินค้า',
        'other'             => 'เรื่องอื่นๆ',
    ];

    public const STATUSES = [
        'pending'     => 'รอดำเนินการ',
        'in_progress' => 'กำลังดำเนินการ',
        'resolved'    => 'ดำเนินการแล้ว',
        'rejected'    => 'ปฏิเสธ',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function sellerGroup(): BelongsTo
    {
        return $this->belongsTo(SellerGroup::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getTopicLabelAttribute(): string
    {
        return self::TOPICS[$this->topic] ?? $this->topic;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
