<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Household;
use App\Models\MushroomAllocation;
use App\Models\MushroomFollowup;
use App\Models\MushroomQuotaDistrict;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductComment;
use App\Models\ProductReview;
use App\Models\ReturnRequest;
use App\Models\User;

class AdminNotificationService
{
    public static function notify(
        string $type,
        string $title,
        ?string $message = null,
        ?string $link = null,
        ?array $meta = null,
        ?int $actorId = null,
    ): AdminNotification {
        return AdminNotification::create([
            'type'     => $type,
            'title'    => $title,
            'message'  => $message,
            'link'     => $link,
            'meta'     => $meta,
            'actor_id' => $actorId,
        ]);
    }

    public static function userRegistered(User $user): void
    {
        self::notify(
            type:    'user_registered',
            title:   'มีผู้สมัครสมาชิกใหม่',
            message: "{$user->name} ({$user->email}) รออนุมัติสิทธิ์",
            link:    '/app/admin/users',
            meta:    ['user_id' => $user->id],
            actorId: $user->id,
        );
    }

    public static function householdCreated(Household $hh, ?User $actor): void
    {
        $name = trim(($hh->prefix ?? '') . $hh->first_name . ' ' . $hh->last_name);
        self::notify(
            type:    'household_created',
            title:   'เพิ่มรายการครัวเรือนใหม่',
            message: "{$hh->household_code} · {$name}" . ($hh->district ? " · {$hh->district}" : ''),
            link:    '/app/households',
            meta:    ['household_id' => $hh->id],
            actorId: $actor?->id,
        );
    }

    public static function quotaCreated(MushroomQuotaDistrict $q, ?User $actor): void
    {
        self::notify(
            type:    'quota_created',
            title:   'บันทึกโควต้าเห็ดใหม่',
            message: "{$q->district} · ปี {$q->year} รอบ {$q->round} · {$q->quota_bags} ถุง",
            link:    '/app/mushroom/quotas',
            meta:    ['quota_id' => $q->id],
            actorId: $actor?->id,
        );
    }

    public static function allocationCreated(MushroomAllocation $a, ?User $actor): void
    {
        $a->loadMissing(['quota', 'household']);
        $hh = trim(($a->household?->first_name ?? '') . ' ' . ($a->household?->last_name ?? ''));
        self::notify(
            type:    'allocation_created',
            title:   'การจัดสรรถุงเห็ดใหม่',
            message: "{$hh} · {$a->bags} ถุง" . ($a->quota?->district ? " · {$a->quota->district}" : ''),
            link:    '/app/mushroom/allocations',
            meta:    ['allocation_id' => $a->id],
            actorId: $actor?->id,
        );
    }

    public static function followupCreated(MushroomFollowup $f, ?User $actor): void
    {
        $f->loadMissing(['allocation.household']);
        $hh = trim(($f->allocation?->household?->first_name ?? '') . ' ' . ($f->allocation?->household?->last_name ?? ''));
        $rev = $f->revenue ? ' · รายได้ ' . number_format((float) $f->revenue, 2) . ' บาท' : '';
        self::notify(
            type:    'followup_created',
            title:   'บันทึกติดตามผลผลิตเห็ดใหม่',
            message: "{$hh} · รอบ {$f->followup_round}{$rev}",
            link:    '/app/mushroom/followups',
            meta:    ['followup_id' => $f->id],
            actorId: $actor?->id,
        );
    }

    // ===== ตลาด (marketplace) =====

    public static function orderPlaced(Order $order): void
    {
        $order->loadMissing(['user:id,name', 'sellerGroup:id,name']);
        $buyer = $order->user?->name ?? 'ลูกค้า';
        $group = $order->sellerGroup?->name;
        self::notify(
            type:    'order_placed',
            title:   'คำสั่งซื้อใหม่',
            message: "#{$order->order_no} · {$buyer}" . ($group ? " · {$group}" : '') . ' · ฿' . number_format((float) $order->total, 0),
            link:    '/app/market/orders',
            meta:    ['order_id' => $order->id, 'order_no' => $order->order_no],
            actorId: $order->user_id,
        );
    }

    public static function paymentSubmitted(Payment $payment): void
    {
        $payment->loadMissing(['order:id,order_no,seller_group_id,user_id', 'order.sellerGroup:id,name']);
        $orderNo = $payment->order?->order_no ?? '';
        $group   = $payment->order?->sellerGroup?->name;
        self::notify(
            type:    'payment_submitted',
            title:   'แจ้งชำระเงินใหม่',
            message: "#{$orderNo}" . ($group ? " · {$group}" : '') . ' · ฿' . number_format((float) $payment->amount, 0),
            link:    '/app/market/payments',
            meta:    ['payment_id' => $payment->id, 'order_id' => $payment->order_id],
            actorId: $payment->order?->user_id,
        );
    }

    public static function returnRequested(ReturnRequest $ret): void
    {
        $ret->loadMissing(['order:id,order_no,seller_group_id', 'order.sellerGroup:id,name']);
        $orderNo  = $ret->order?->order_no ?? '';
        $typeMap  = ['return' => 'คืนสินค้า', 'refund' => 'คืนเงิน', 'claim' => 'เคลม'];
        $typeLabel = $typeMap[$ret->type] ?? $ret->type;
        self::notify(
            type:    'return_requested',
            title:   'คำขอคืน/เคลมใหม่',
            message: "#{$orderNo} · {$typeLabel}: {$ret->reason}",
            link:    '/app/market/returns',
            meta:    ['return_id' => $ret->id, 'order_id' => $ret->order_id],
            actorId: $ret->user_id,
        );
    }

    public static function newReview(ProductReview $review): void
    {
        $review->loadMissing(['product:id,name', 'user:id,name']);
        $product = $review->product?->name ?? 'สินค้า';
        $stars   = str_repeat('★', (int) $review->rating) . str_repeat('☆', 5 - (int) $review->rating);
        self::notify(
            type:    'new_review',
            title:   'มีรีวิวสินค้าใหม่',
            message: "{$product} · {$stars} · {$review->user?->name}",
            link:    '/app/market/reviews',
            meta:    ['review_id' => $review->id, 'product_id' => $review->product_id],
            actorId: $review->user_id,
        );
    }

    public static function newComment(ProductComment $comment): void
    {
        $comment->loadMissing(['product:id,name', 'user:id,name']);
        $product = $comment->product?->name ?? 'สินค้า';
        $body    = mb_strimwidth($comment->body, 0, 50, '...');
        self::notify(
            type:    'new_comment',
            title:   'มีความคิดเห็นสินค้าใหม่',
            message: "{$product} · {$comment->user?->name}: {$body}",
            link:    '/app/market/comments',
            meta:    ['comment_id' => $comment->id, 'product_id' => $comment->product_id],
            actorId: $comment->user_id,
        );
    }
}
