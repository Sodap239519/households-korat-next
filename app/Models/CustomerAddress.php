<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'user_id', 'label', 'name', 'phone',
        'address', 'sub_district', 'district', 'province', 'postal_code',
        'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
