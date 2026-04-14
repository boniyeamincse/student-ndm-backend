<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyLeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'relation_id' => $this->id,
            'relation_no' => $this->relation_no,
            'relation_type' => $this->relation_type?->value,
            'committee' => [
                'id' => $this->committee?->id,
                'name' => $this->committee?->name,
            ],
            'superior' => [
                'member_id' => $this->superiorAssignment?->member?->id,
                'member_no' => $this->superiorAssignment?->member?->member_no,
                'full_name' => $this->superiorAssignment?->member?->full_name,
                'position' => [
                    'id' => $this->superiorAssignment?->position?->id,
                    'name' => $this->superiorAssignment?->position?->name,
                    'is_leadership' => $this->superiorAssignment?->position?->is_leadership,
                ],
            ],
        ];
    }
}
