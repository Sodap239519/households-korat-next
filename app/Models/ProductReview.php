<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    use HasFactory;

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_HIDDEN    = 'hidden';

    protected $fillable = [
        'product_id',
        'order_id',
        'user_id',
        'rating',
        'title',
        'comment',
        'images',
        'status',
        'reply',
        'replied_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
    ];

    protected $appends = ['image_urls'];

    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($p) => asset('storage/' . $p))
            ->all();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repliedBy()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}
