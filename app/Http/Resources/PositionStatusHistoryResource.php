<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'old_active_state' => $this->old_active_state,
            'new_active_state' => $this->new_active_state,
            'note' => $this->note,
            'changed_by' => $this->whenLoaded('changedByUser', fn () => [
                'id' => $this->changedByUser?->id,
                'name' => $this->changedByUser?->name,
                'email' => $this->changedByUser?->email,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
