<?php

namespace App\DataTransferObjects;

use Carbon\CarbonInterface;

readonly class CreatePromoCodeData
{
    public function __construct(
        public string $partnerId,
        public string $code,
        public int $bonusMb = 200,
        public float $partnerReward = 1.50,
        public string $type = 'standard',
        public ?CarbonInterface $expiresAt = null,
        public ?int $maxUsage = null,
        public bool $isActive = true,
        public bool $deactivateExistingActive = false,
    ) {}
}
