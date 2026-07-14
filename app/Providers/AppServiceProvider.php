<?php

namespace App\Providers;

use App\Models\ProductReview;
use App\Observers\ProductReviewObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        ProductReview::observe(ProductReviewObserver::class);
    }
}
