<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = [
        'type', 'title', 'message', 'link', 'meta', 'actor_id', 'read_at',
    ];

    protected $casts = [
        'meta'    => 'array',
        'read_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeUnread($q)
    {
        return $q->whereNull('read_at');
    }
}
