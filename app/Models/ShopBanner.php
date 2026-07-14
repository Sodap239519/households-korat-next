<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopBanner extends Model
{
    protected $fillable = [
        'tag', 'image_path', 'title', 'subtitle', 'gradient',
        'cta_label', 'cta_icon', 'cta_color',
        'link_type', 'link_value',
        'emojis', 'sort_order', 'is_active', 'aspect_ratio',
    ];

    protected $casts = [
        'emojis'    => 'array',
        'is_active' => 'boolean',
    ];
}
