<?php

namespace App\DataTransferObjects;

use App\Support\Money;

readonly class PurchaseOffer
{
    public function __construct(
        public Money $listPrice,
        public Money $discountAmount,
        public string $discountPercentage,
        public Money $chargedAmount,
        public bool $discountApplied,
    ) {}

    public function currency(): string
    {
        return $this->chargedAmount->currency;
    }
}
