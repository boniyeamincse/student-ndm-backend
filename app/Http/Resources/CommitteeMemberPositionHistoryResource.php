<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeMemberPositionHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'old_position' => $this->whenLoaded('oldPosition', fn () => [
                'id' => $this->oldPosition?->id,
                'name' => $this->oldPosition?->name,
                'hierarchy_rank' => $this->oldPosition?->hierarchy_rank,
            ]),
            'new_position' => $this->whenLoaded('newPosition', fn () => [
                'id' => $this->newPosition?->id,
                'name' => $this->newPosition?->name,
                'hierarchy_rank' => $this->newPosition?->hierarchy_rank,
            ]),
            'changed_by' => $this->whenLoaded('changedByUser', fn () => [
                'id' => $this->changedByUser?->id,
                'name' => $this->changedByUser?->name,
                'email' => $this->changedByUser?->email,
            ]),
            'note' => $this->note,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
