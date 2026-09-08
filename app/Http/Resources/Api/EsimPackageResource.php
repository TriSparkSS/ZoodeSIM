<?php

namespace App\Http\Resources\Api;

use App\DataTransferObjects\PricedEsimPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PricedEsimPackage
 */
class EsimPackageResource extends JsonResource
{
    /**
     * @return array{package_code: string, name: string, price: float, currency: string, location: string, list_price: float, discount_amount: float, discount_percentage: float}
     */
    public function toArray(Request $request): array
    {
        /** @var PricedEsimPackage $package */
        $package = $this->resource;

        return $package->toArray();
    }
}
