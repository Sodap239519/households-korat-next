<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Household;
use App\Models\MushroomAllocation;
use App\Models\MushroomFollowup;
use App\Models\MushroomQuotaDistrict;
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
}
