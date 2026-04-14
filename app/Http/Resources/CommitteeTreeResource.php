<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeTreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'] ?? null,
            'name' => $this['name'] ?? null,
            'committee_no' => $this['committee_no'] ?? null,
            'committee_type' => $this['committee_type'] ?? null,
            'status' => $this['status'] ?? null,
            'children' => self::collection($this['children'] ?? []),
        ];
    }
}
