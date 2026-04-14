<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CommitteeType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'code',
        'hierarchy_order',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CommitteeType $type) {
            if (empty($type->uuid)) {
                $type->uuid = (string) Str::uuid();
            }

            if (empty($type->slug) && ! empty($type->name)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    public function committees(): HasMany
    {
        return $this->hasMany(Committee::class, 'committee_type_id');
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'committee_type_position', 'committee_type_id', 'position_id')
            ->withTimestamps();
    }
}
