<?php

namespace App\Models;

use App\Enum\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MembershipApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'application_no',
        'full_name',
        'father_name',
        'mother_name',
        'gender',
        'date_of_birth',
        'mobile',
        'email',
        'photo',
        'national_id',
        'student_id',
        'blood_group',
        'occupation',
        'educational_institution',
        'department',
        'academic_year',
        'address_line',
        'village_area',
        'post_office',
        'union_name',
        'upazila_name',
        'district_name',
        'division_name',
        'emergency_contact_name',
        'emergency_contact_phone',
        'reference_member_id',
        'desired_committee_level',
        'desired_committee_id',
        'motivation',
        'status',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'remarks',
        'source',
        'submitted_ip',
    ];

    protected function casts(): array
    {
        return [
            'status'      => ApplicationStatus::class,
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'date_of_birth' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MembershipApplication $app) {
            if (empty($app->uuid)) {
                $app->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function referenceMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reference_member_id');
    }

    /** Placeholder relation for future committee module */
    public function desiredCommittee(): BelongsTo
    {
        // TODO: replace target model when committee module is implemented
        return $this->belongsTo(Member::class, 'desired_committee_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'membership_application_id')
                    ->orderBy('created_at');
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class, 'membership_application_id');
    }
}
