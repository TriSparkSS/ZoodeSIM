<?php

namespace App\DataTransferObjects;

readonly class PricedEsimPackage
{
    public function __construct(
        public string $packageCode,
        public string $name,
        public float $price,
        public string $currency,
        public string $location,
        public float $listPrice,
        public float $discountAmount,
        public float $discountPercentage,
    ) {}

    public static function fromOffer(EsimPackageData $package, PurchaseOffer $offer): self
    {
        return new self(
            packageCode: $package->packageCode,
            name: $package->name,
            price: $offer->chargedAmount->toFloat(),
            currency: $offer->currency(),
            location: $package->location,
            listPrice: $offer->listPrice->toFloat(),
            discountAmount: $offer->discountAmount->toFloat(),
            discountPercentage: (float) $offer->discountPercentage,
        );
    }

    /**
     * @return array{package_code: string, name: string, price: float, currency: string, location: string, list_price: float, discount_amount: float, discount_percentage: float}
     */
    public function toArray(): array
    {
        return [
            'package_code' => $this->packageCode,
            'name' => $this->name,
            'price' => $this->price,
            'currency' => $this->currency,
            'location' => $this->location,
            'list_price' => $this->listPrice,
            'discount_amount' => $this->discountAmount,
            'discount_percentage' => $this->discountPercentage,
        ];
    }
}
