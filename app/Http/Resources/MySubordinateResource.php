<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MySubordinateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'relation_id' => $this->id,
            'relation_no' => $this->relation_no,
            'relation_type' => $this->relation_type?->value,
            'is_primary' => (bool) $this->is_primary,
            'is_active' => (bool) $this->is_active,
            'committee' => [
                'id' => $this->committee?->id,
                'name' => $this->committee?->name,
            ],
            'subordinate' => [
                'member_id' => $this->subordinateAssignment?->member?->id,
                'member_no' => $this->subordinateAssignment?->member?->member_no,
                'full_name' => $this->subordinateAssignment?->member?->full_name,
                'position' => [
                    'id' => $this->subordinateAssignment?->position?->id,
                    'name' => $this->subordinateAssignment?->position?->name,
                ],
            ],
        ];
    }
}
