<?php

namespace App\Observers;

use App\Models\ProductReview;

/**
 * อัปเดต rating_avg / rating_count ของสินค้าทุกครั้งที่รีวิวเปลี่ยน
 */
class ProductReviewObserver
{
    public function saved(ProductReview $review): void
    {
        $review->product?->recalculateRating();
    }

    public function deleted(ProductReview $review): void
    {
        $review->product?->recalculateRating();
    }
}
