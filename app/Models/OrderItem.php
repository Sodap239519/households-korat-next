<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'unit_price',
        'qty',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'qty'        => 'integer',
        'line_total' => 'decimal:2',
    ];

    // คำนวณยอดรวมรายการอัตโนมัติ
    public static function boot()
    {
        parent::boot();

        static::saving(function (OrderItem $item) {
            $item->line_total = (float) $item->unit_price * (int) $item->qty;
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
