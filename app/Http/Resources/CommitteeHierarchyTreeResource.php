<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeHierarchyTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'assignment_id' => $this['assignment_id'] ?? null,
            'member_id' => $this['member_id'] ?? null,
            'member_name' => $this['member_name'] ?? null,
            'member_no' => $this['member_no'] ?? null,
            'photo_url' => $this['photo_url'] ?? null,
            'position' => $this['position'] ?? null,
            'position_rank' => $this['position_rank'] ?? null,
            'is_leadership' => $this['is_leadership'] ?? false,
            'relation_type' => $this['relation_type'] ?? null,
            'children' => self::collection(collect($this['children'] ?? [])),
        ];
    }
}
