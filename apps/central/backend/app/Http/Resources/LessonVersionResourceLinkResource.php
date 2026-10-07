<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonVersionResourceLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $linkedResource = $this->resource->resource;

        return [
            'id' => (int) $this->id,
            'role' => $this->role->value,
            'display_order' => (int) $this->display_order,
            'is_required' => (bool) $this->is_required,

            'resource' => [
                'id' => (int) $linkedResource->id,
                'code' => $linkedResource->code,
                'type' => $linkedResource->type,
                'status' => $linkedResource->status,
            ],
        ];
    }
}