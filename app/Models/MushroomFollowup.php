<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MushroomFollowup extends Model
{
    use HasFactory;

    protected $table = 'mushroom_followups';

    protected $fillable = [
        'allocation_id',
        'followup_round',
        'followup_date',
        'harvest_kg',
        'sold_kg',
        'price_per_kg',
        'revenue',
        'sale_channel',
        'sale_place',
        'enterprise_member',
        'enterprise_name',
        'note',
    ];

    protected $casts = [
        'followup_round'    => 'integer',
        'followup_date'     => 'date',
        'harvest_kg'        => 'decimal:3',
        'sold_kg'           => 'decimal:3',
        'price_per_kg'      => 'decimal:2',
        'revenue'           => 'decimal:2',
        'enterprise_member' => 'boolean',
    ];

    // คำนวณรายได้อัตโนมัติ
    public static function boot()
    {
        parent::boot();

        static::saving(function (MushroomFollowup $followup) {
            if (is_null($followup->revenue) && $followup->sold_kg && $followup->price_per_kg) {
                $followup->revenue = (float) $followup->sold_kg * (float) $followup->price_per_kg;
            }
        });
    }

    public function allocation()
    {
        return $this->belongsTo(MushroomAllocation::class, 'allocation_id');
    }
}
