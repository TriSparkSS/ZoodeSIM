<?php

namespace App\DataTransferObjects;

use App\Models\PromoCode;

readonly class PromoValidationResult
{
    public function __construct(
        public bool $valid,
        public int $bonusMb = 0,
        public ?string $partnerName = null,
        public ?string $reason = null,
        public ?string $bonusType = null,
        public float|int $bonusAmount = 0,
    ) {}

    public static function valid(PromoCode $promo, string $partnerName): self
    {
        $bonus = $promo->apiBonusPayload();

        return new self(
            valid: true,
            bonusMb: $bonus['bonus_mb'],
            partnerName: $partnerName,
            bonusType: $bonus['bonus_type'],
            bonusAmount: $bonus['bonus_amount'],
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
     * @return array{valid: bool, bonus_type: string|null, bonus_amount: float|int, bonus_mb: int, partner_name: string|null, reason: string|null}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'bonus_type' => $this->bonusType,
            'bonus_amount' => $this->bonusAmount,
            'bonus_mb' => $this->bonusMb,
            'partner_name' => $this->partnerName,
            'reason' => $this->reason,
        ];
    }
}
