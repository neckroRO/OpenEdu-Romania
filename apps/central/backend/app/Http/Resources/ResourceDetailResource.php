<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $publishedVersion = $this->versions->first();

        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'type' => $this->type,

            'version' => $publishedVersion
                ? new ResourceVersionResource($publishedVersion)
                : null,
        ];
    }
}
