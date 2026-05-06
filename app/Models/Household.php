<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Household extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'is_active'         => 'boolean',
        'has_mushroom_area' => 'boolean',
        'has_electricity'   => 'boolean',
        'ever_agriculture'  => 'boolean',
        'ever_mushroom'     => 'boolean',
        'social_media_use'  => 'boolean',
        'group_member'      => 'boolean',
        'passed'            => 'boolean',
        'completed'         => 'boolean',
        'dob'               => 'date',
        'survey_date'       => 'date',
        'income_month'      => 'decimal:2',
        'expense_month'     => 'decimal:2',
        'debt_amount'       => 'decimal:2',
        'mushroom_area_size'=> 'decimal:2',
        'distance_to_market_km' => 'decimal:2',
        'initial_investment'=> 'decimal:2',
        'poverty_score'     => 'decimal:2',
        'motivation_score'  => 'decimal:2',
        'experience_score'  => 'decimal:2',
        'grouping_score'    => 'decimal:2',
        'potential_score'   => 'decimal:2',
        'area_score'        => 'decimal:2',
        'market_score'      => 'decimal:2',
        'total_score'       => 'decimal:2',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(($this->prefix ?? '') . $this->first_name . ' ' . $this->last_name);
    }

    public function allocations()
    {
        return $this->hasMany(MushroomAllocation::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
