<?php

namespace App\DataTransferObjects;

use Illuminate\Support\Carbon;

readonly class ResellPortalBalance
{
    public function __construct(
        public bool $available,
        public ?string $amount,
        public ?string $currency,
        public Carbon $fetchedAt,
        public bool $fromCache,
    ) {}

    public function formatted(): ?string
    {
        if (! $this->available || $this->amount === null || $this->currency === null) {
            return null;
        }

        return number_format((float) $this->amount, 2).' '.$this->currency;
    }
}
