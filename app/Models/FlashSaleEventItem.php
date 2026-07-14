<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleEventItem extends Model
{
    protected $fillable = [
        'event_id',
        'product_id',
        'seller_group_id',
        'sale_price',
        'stock_limit',
        'sort_order',
    ];

    protected $casts = [
        'sale_price'  => 'decimal:2',
        'stock_limit' => 'integer',
        'sort_order'  => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(FlashSaleEvent::class, 'event_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->with(['images' => fn($q) => $q->where('is_primary', true)]);
    }

    public function sellerGroup()
    {
        return $this->belongsTo(SellerGroup::class);
    }
}
