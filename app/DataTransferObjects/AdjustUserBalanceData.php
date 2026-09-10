<?php

namespace App\DataTransferObjects;

readonly class AdjustUserBalanceData
{
    public function __construct(
        public string $direction,
        public string $amount,
        public string $note = '',
    ) {}

    public function isCredit(): bool
    {
        return $this->direction === 'credit';
    }

    public function trimmedNote(): string
    {
        return trim($this->note);
    }
}
