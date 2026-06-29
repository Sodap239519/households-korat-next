<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;

class ChatMessage extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'sender_type', 'body', 'is_read', 'product_id'];
    protected $casts    = ['is_read' => 'boolean'];

    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function sender(): BelongsTo       { return $this->belongsTo(User::class, 'sender_id'); }
    public function product(): BelongsTo      { return $this->belongsTo(Product::class)->select(['id', 'name', 'slug', 'price', 'sale_price', 'rating_avg', 'rating_count']); }
}
