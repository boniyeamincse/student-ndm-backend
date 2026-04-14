<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type'        => $this->resource['type']        ?? null,
            'label'       => $this->resource['label']       ?? null,
            'count'       => $this->resource['count']       ?? 0,
            'action_url'  => $this->resource['action_url']  ?? null,
            'priority'    => $this->resource['priority']    ?? 'normal',
        ];
    }
}
