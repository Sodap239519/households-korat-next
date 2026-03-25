<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MushroomQuotaDistrict extends Model
{
    use HasFactory;

    protected $table = 'mushroom_quota_districts';

    protected $fillable = [
        'district',
        'province',
        'year',
        'round',
        'quota_bags',
        'is_active',
        'note',
    ];

    protected $casts = [
        'year'       => 'integer',
        'round'      => 'integer',
        'quota_bags' => 'integer',
        'is_active'  => 'boolean',
    ];

    // คำนวณจำนวนขีด (1 ถุง = 2 ขีด)
    public function getQuotaQidAttribute(): int
    {
        return $this->quota_bags * 2;
    }

    // คำนวณน้ำหนัก กก. (1 ขีด = 0.1 กก.)
    public function getQuotaKgAttribute(): float
    {
        return $this->quota_bags * 0.2;
    }

    // รายได้พื้นฐาน (1 ถุง = 12 บาท)
    public function getBaselineRevenueAttribute(): float
    {
        return $this->quota_bags * 12.0;
    }

    public function allocations()
    {
        return $this->hasMany(MushroomAllocation::class, 'quota_id');
    }
}
