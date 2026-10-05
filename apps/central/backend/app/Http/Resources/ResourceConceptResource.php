<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceConceptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $linkedResource = $this->resource->getRelation('resource');

        $publishedVersion = $linkedResource
            ->versions
            ->first();

        return [
            'id' => (int) $this->id,
            'is_primary' => (bool) $this->is_primary,
            'display_order' => (int) $this->display_order,

            'resource' => [
                'id' => (int) $linkedResource->id,
                'code' => $linkedResource->code,
                'type' => $linkedResource->type,

                'version' => $publishedVersion
                    ? new ResourceVersionSummaryResource($publishedVersion)
                    : null,
            ],
        ];
    }
}
