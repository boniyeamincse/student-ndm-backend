<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'old_status'     => $this->old_status,
            'new_status'     => $this->new_status,
            'note'           => $this->note,
            'changed_by'     => $this->whenLoaded('changedByUser', fn () => [
                'id'   => $this->changedByUser?->id,
                'name' => $this->changedByUser?->name,
            ]),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
