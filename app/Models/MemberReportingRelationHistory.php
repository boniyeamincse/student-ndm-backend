<?php

namespace App\Models;

use App\Enum\ReportingRelationHistoryAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberReportingRelationHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'member_reporting_relation_id',
        'action',
        'old_values',
        'new_values',
        'changed_by',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => ReportingRelationHistoryAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function memberReportingRelation(): BelongsTo
    {
        return $this->belongsTo(MemberReportingRelation::class, 'member_reporting_relation_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
