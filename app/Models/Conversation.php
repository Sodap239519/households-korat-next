<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'seller_group_id', 'seller_user_id', 'customer_id', 'last_message_at',
        'last_message_preview', 'is_read_by_seller', 'is_read_by_customer',
    ];

    protected $casts = [
        'last_message_at'      => 'datetime',
        'is_read_by_seller'    => 'boolean',
        'is_read_by_customer'  => 'boolean',
    ];

    public function sellerGroup(): BelongsTo  { return $this->belongsTo(SellerGroup::class); }
    public function sellerUser(): BelongsTo   { return $this->belongsTo(User::class, 'seller_user_id'); }
    public function customer(): BelongsTo     { return $this->belongsTo(User::class, 'customer_id'); }
    public function messages(): HasMany       { return $this->hasMany(ChatMessage::class); }
}
