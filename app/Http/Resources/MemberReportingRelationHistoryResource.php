<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberReportingRelationHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action?->value,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
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
