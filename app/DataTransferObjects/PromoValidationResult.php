<?php

namespace App\DataTransferObjects;

readonly class PromoValidationResult
{
    public function __construct(
        public bool $valid,
        public int $bonusMb = 0,
        public ?string $partnerName = null,
        public ?string $reason = null,
    ) {}

    public static function valid(int $bonusMb, string $partnerName): self
    {
        return new self(
            valid: true,
            bonusMb: $bonusMb,
            partnerName: $partnerName,
        );
    }

    public static function invalid(string $reason): self
    {
        return new self(
            valid: false,
            reason: $reason,
        );
    }

    /**
     * @return array{valid: bool, bonus_mb: int, partner_name: string|null, reason: string|null}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'bonus_mb' => $this->bonusMb,
            'partner_name' => $this->partnerName,
            'reason' => $this->reason,
        ];
    }
}
