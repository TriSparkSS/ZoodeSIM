<?php

namespace App\Models\Concerns;

use App\Models\PromoCode;
use App\Models\PromoUsage;

trait FormatsUserBonus
{
    public function isUsdBonus(): bool
    {
        return $this->bonus_type === PromoCode::BONUS_TYPE_USD;
    }

    public function userBonusLabel(): string
    {
        if ($this->isUsdBonus()) {
            return '$'.number_format((float) $this->bonus_amount, 2);
        }

        $mb = $this instanceof PromoUsage
            ? (int) $this->bonus_mb_given
            : (int) $this->bonus_mb;

        return $mb.' MB';
    }

    /**
     * @return array{bonus_type: string, bonus_amount: float|int, bonus_mb: int}
     */
    public function apiBonusPayload(): array
    {
        $isUsd = $this->isUsdBonus();
        $mb = $this instanceof PromoUsage
            ? (int) $this->bonus_mb_given
            : (int) $this->bonus_mb;

        return [
            'bonus_type' => $isUsd ? PromoCode::BONUS_TYPE_USD : PromoCode::BONUS_TYPE_MB,
            'bonus_amount' => $isUsd ? (float) $this->bonus_amount : $mb,
            'bonus_mb' => $isUsd ? 0 : $mb,
        ];
    }
}
