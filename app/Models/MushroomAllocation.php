<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MushroomAllocation extends Model
{
    use HasFactory;

    protected $table = 'mushroom_allocations';

    protected $fillable = [
        'quota_id',
        'household_id',
        'bags',
        'allocated_date',
        'status',
        'note',
    ];

    protected $casts = [
        'bags'           => 'integer',
        'allocated_date' => 'date',
    ];

    public function quota()
    {
        return $this->belongsTo(MushroomQuotaDistrict::class, 'quota_id');
    }

    public function household()
    {
        return $this->belongsTo(Household::class);
    }

    public function followups()
    {
        return $this->hasMany(MushroomFollowup::class, 'allocation_id');
    }
}
