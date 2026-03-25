<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Household extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'household_code',
        'prefix',
        'first_name',
        'last_name',
        'id_card',
        'phone',
        'village',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'is_active',
        'note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ชื่อเต็ม
    public function getFullNameAttribute(): string
    {
        return trim(($this->prefix ?? '') . $this->first_name . ' ' . $this->last_name);
    }

    public function allocations()
    {
        return $this->hasMany(MushroomAllocation::class);
    }
}
