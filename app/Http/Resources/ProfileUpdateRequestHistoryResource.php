<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileUpdateRequestHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'old_status' => $this->old_status?->value,
            'new_status' => $this->new_status?->value,
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
