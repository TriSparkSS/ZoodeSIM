<?php

namespace App\Http\Resources\Api\Admin;

use App\Models\PricingSlab;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PricingSlab
 */
class PricingSlabResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'min_amount' => (string) $this->min_amount,
            'max_amount' => (string) $this->max_amount,
            'percentage' => (string) $this->percentage,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
