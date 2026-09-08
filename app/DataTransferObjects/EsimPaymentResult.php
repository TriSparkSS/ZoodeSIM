<?php

namespace App\DataTransferObjects;

readonly class EsimPaymentResult
{
    public function __construct(
        public bool $successful,
        public string $status,
        public ?string $reason = null,
    ) {}

    public static function paid(): self
    {
        return new self(true, 'paid');
    }

    public static function failed(string $reason = 'payment_failed'): self
    {
        return new self(false, 'failed', $reason);
    }
}
