<?php

namespace App\DataTransferObjects;

use App\Support\Money;

readonly class EsimPriceQuote
{
    public function __construct(
        public Money $providerCost,
        public Money $markupAmount,
        public Money $customerPrice,
        public string $markupPercentage,
        public ?string $slabId = null,
    ) {}

    public function currency(): string
    {
        return $this->customerPrice->currency;
    }

    /**
     * @return array{provider_cost: string, markup_percentage: string, markup_amount: string, customer_price: string, currency: string}
     */
    public function toArray(): array
    {
        return [
            'provider_cost' => $this->providerCost->toDecimal(),
            'markup_percentage' => $this->markupPercentage,
            'markup_amount' => $this->markupAmount->toDecimal(),
            'customer_price' => $this->customerPrice->toDecimal(),
            'currency' => $this->currency(),
        ];
    }

    /**
     * Admin preview includes the matching slab id; users never see this.
     *
     * @return array{provider_cost: string, markup_percentage: string, markup_amount: string, customer_price: string, currency: string, slab_id: string|null}
     */
    public function toAdminPreview(): array
    {
        return [
            ...$this->toArray(),
            'slab_id' => $this->slabId,
        ];
    }
}
