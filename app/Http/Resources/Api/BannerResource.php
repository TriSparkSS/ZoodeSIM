<?php

namespace App\Http\Resources\Api;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Banner
 */
class BannerResource extends JsonResource
{
    /**
     * @return array{id: string, title: string, image_url: string|null, link_url: string|null, sort_order: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_url' => $this->imageUrl(),
            'link_url' => $this->link_url,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
