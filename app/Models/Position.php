<?php

namespace App\Models;

use App\Enum\PositionCategory;
use App\Enum\PositionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'code',
        'short_name',
        'hierarchy_rank',
        'display_order',
        'description',
        'category',
        'scope',
        'is_leadership',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => PositionCategory::class,
            'scope' => PositionScope::class,
            'is_leadership' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Position $position) {
            if (empty($position->uuid)) {
                $position->uuid = (string) Str::uuid();
            }

            if (empty($position->slug) && ! empty($position->name)) {
                $position->slug = Str::slug($position->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function committeeTypes(): BelongsToMany
    {
        return $this->belongsToMany(CommitteeType::class, 'committee_type_position', 'position_id', 'committee_type_id')
            ->withTimestamps();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PositionStatusHistory::class, 'position_id')->latest('created_at');
    }

    public function committeeAssignments(): HasMany
    {
        return $this->hasMany(CommitteeMemberAssignment::class, 'position_id');
    }

    // Future placeholder: committeeMemberAssignments()
    // Future placeholder: leadershipAssignments()
    // Future placeholder: reportingRelations()
}
