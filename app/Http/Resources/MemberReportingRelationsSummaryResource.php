<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberReportingRelationsSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_relations' => $this['total_relations'] ?? 0,
            'active_relations' => $this['active_relations'] ?? 0,
            'inactive_relations' => $this['inactive_relations'] ?? 0,
            'primary_relations' => $this['primary_relations'] ?? 0,
            'by_relation_type' => $this['by_relation_type'] ?? [],
            'by_committee' => $this['by_committee'] ?? [],
            'by_committee_type' => $this['by_committee_type'] ?? [],
        ];
    }
}
