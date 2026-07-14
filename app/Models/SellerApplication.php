<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SellerApplication extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING              = 'pending';
    public const STATUS_ADMIN_APPROVED       = 'admin_approved';
    public const STATUS_ESCALATED            = 'escalated';
    public const STATUS_APPROVED             = 'approved';
    public const STATUS_REJECTED             = 'rejected';
    public const STATUS_REVISION_REQUESTED   = 'revision_requested';

    /** นาทีที่รอ admin ก่อน escalate */
    public const ESCALATE_MINUTES = 60;

    protected $fillable = [
        'token', 'applicant_user_id',
        'applicant_name', 'applicant_phone', 'applicant_email', 'applicant_id_card',
        'business_name', 'logo_path', 'business_type', 'business_categories', 'district', 'sub_district', 'business_address',
        'lat', 'lng',
        'products_description', 'note', 'documents',
        'requested_group_id',
        'status',
        'admin_id', 'admin_note', 'admin_reviewed_at',
        'escalated_at',
        'superadmin_id', 'superadmin_note', 'superadmin_reviewed_at',
        'revision_note',
        'assigned_group_id', 'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'documents'              => 'array',
            'business_categories'    => 'array',
            'lat'                    => 'decimal:6',
            'lng'                    => 'decimal:6',
            'admin_reviewed_at'      => 'datetime',
            'escalated_at'           => 'datetime',
            'superadmin_reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->token)) {
                $model->token = Str::random(40);
            }
        });
    }

    // ===== Relations =====

    public function applicantUser()
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function requestedGroup()
    {
        return $this->belongsTo(SellerGroup::class, 'requested_group_id');
    }

    public function assignedGroup()
    {
        return $this->belongsTo(SellerGroup::class, 'assigned_group_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function superadmin()
    {
        return $this->belongsTo(User::class, 'superadmin_id');
    }

    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    // ===== Helpers =====

    /** ป้ายสถานะสำหรับแสดงผล */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING             => 'รอพิจารณา',
            self::STATUS_ADMIN_APPROVED      => 'admin อนุมัติแล้ว — รอ superadmin',
            self::STATUS_ESCALATED           => 'เลยกำหนด — ส่งต่อ superadmin',
            self::STATUS_APPROVED            => 'อนุมัติแล้ว',
            self::STATUS_REJECTED            => 'ปฏิเสธ',
            self::STATUS_REVISION_REQUESTED  => 'ขอแก้ไข / ตีกลับ',
            default                          => $this->status,
        };
    }

    /** รอ superadmin พิจารณา: pending (อนุมัติตรงได้เลย), admin_approved, escalated */
    public function needsSuperAdminReview(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ADMIN_APPROVED,
            self::STATUS_ESCALATED,
        ], true);
    }
}
