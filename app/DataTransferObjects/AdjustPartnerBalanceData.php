<?php

namespace App\DataTransferObjects;

readonly class AdjustPartnerBalanceData
{
    public function __construct(
        public string $direction,
        public string $amount,
        public string $note,
    ) {}

    public function isCredit(): bool
    {
        return $this->direction === 'credit';
    }
}
