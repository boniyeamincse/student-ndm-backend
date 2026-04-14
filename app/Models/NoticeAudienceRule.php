<?php

namespace App\Models;

use App\Enum\NoticeAudienceRuleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeAudienceRule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'notice_id',
        'rule_type',
        'rule_value',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'rule_type' => NoticeAudienceRuleType::class,
            'created_at' => 'datetime',
        ];
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class, 'notice_id');
    }
}
