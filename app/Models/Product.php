<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'seller_group_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price',
        'sale_price',
        'stock_qty',
        'unit',
        'district',
        'status',
        'is_featured',
        'view_count',
        'rating_avg',
        'rating_count',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'sale_price'   => 'decimal:2',
        'stock_qty'    => 'integer',
        'is_featured'  => 'boolean',
        'view_count'   => 'integer',
        'rating_avg'   => 'float',
        'rating_count' => 'integer',
    ];

    protected $appends = ['effective_price', 'primary_image_url'];

    /** ราคาที่ใช้จริง (ราคาลดถ้ามี ไม่งั้นราคาปกติ) */
    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $img = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_primary', true) ?? $this->images->first()
            : $this->images()->orderByDesc('is_primary')->orderBy('sort_order')->first();

        return $img ? asset('storage/' . $img->path) : null;
    }

    public function sellerGroup()
    {
        return $this->belongsTo(SellerGroup::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function comments()
    {
        return $this->hasMany(ProductComment::class);
    }

    /** อัปเดตคะแนนรีวิวเฉลี่ย + จำนวน จากรีวิวที่ published */
    public function recalculateRating(): void
    {
        $stats = $this->reviews()->where('status', 'published')
            ->selectRaw('AVG(rating) as avg, COUNT(*) as cnt')->first();

        $this->forceFill([
            'rating_avg'   => round((float) ($stats->avg ?? 0), 2),
            'rating_count' => (int) ($stats->cnt ?? 0),
        ])->save();
    }
}
