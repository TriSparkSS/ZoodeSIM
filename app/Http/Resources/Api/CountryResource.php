<?php

namespace App\Http\Resources\Api;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Country
 */
class CountryResource extends JsonResource
{
    /**
     * @return array{code: string, name: string, image_url: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'image_url' => $this->imageUrl(),
        ];
    }
}
