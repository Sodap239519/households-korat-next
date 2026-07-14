<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FlashSaleEvent extends Model
{
    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['is_currently_active', 'is_upcoming', 'status_label'];

    public function getIsCurrentlyActiveAttribute(): bool
    {
        return $this->is_active
            && $this->starts_at->lte(now())
            && $this->ends_at->gte(now());
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->is_active && $this->starts_at->gt(now());
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) return 'ปิด';
        if ($this->is_currently_active) return 'กำลังดำเนินการ';
        if ($this->is_upcoming) return 'กำลังจะมาถึง';
        return 'สิ้นสุดแล้ว';
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)
                 ->where('starts_at', '<=', now())
                 ->where('ends_at', '>=', now());
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('starts_at', '>', now());
    }

    public function items()
    {
        return $this->hasMany(FlashSaleEventItem::class, 'event_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
