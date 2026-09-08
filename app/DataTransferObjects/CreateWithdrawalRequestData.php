<?php

namespace App\DataTransferObjects;

readonly class CreateWithdrawalRequestData
{
    public function __construct(
        public string $amount,
        public string $method,
        public string $payoutDetails,
    ) {}
}
