<?php

namespace App\Services\Referral;

use App\Services\Content\ProgramSettingService;
use App\Support\Money;

class ReferralProgramSettings
{
    public function __construct(
        protected ProgramSettingService $settings,
    ) {}

    public function firstPurchaseDiscountPercent(): string
    {
        return $this->decimal('first_purchase_discount', (string) config('referral.first_purchase_discount', '5.00'));
    }

    public function firstPurchaseDiscountDays(): int
    {
        return max(0, (int) $this->raw('first_purchase_discount_days', (string) config('referral.first_purchase_discount_days', 7)));
    }

    public function cashbackPercent(): string
    {
        return $this->decimal('cashback_percent', (string) config('referral.cashback_percent', '10.00'));
    }

    public function cashbackMinAmount(): Money
    {
        $currency = (string) config('pricing.currency', 'USD');

        return Money::fromDecimal(
            $this->decimal('cashback_min_amount', (string) config('referral.cashback_min_amount', '10.00')),
            $currency,
        );
    }

    public function firstPurchaseCommissionPercent(): string
    {
        return $this->decimal('purchase_commission', (string) config('referral.purchase_commission', '10.00'));
    }

    public function subsequentPurchaseCommissionPercent(): string
    {
        return $this->decimal('subsequent_purchase_commission', (string) config('referral.subsequent_purchase_commission', '5.00'));
    }

    public function defaultUserBonusMb(): int
    {
        return max(0, (int) $this->raw('user_bonus', (string) config('referral.user_bonus', 200)));
    }

    public function defaultRegistrationReward(): string
    {
        return $this->decimal('registration_reward', (string) config('referral.registration_reward', '1.50'));
    }

    public function percentLabel(string $decimal): string
    {
        return rtrim(rtrim($decimal, '0'), '.').'%';
    }

    /**
     * @return array<int, string>
     */
    public function milestones(): array
    {
        $defaults = config('referral.milestones', [
            10 => '5.00',
            50 => '30.00',
            100 => '75.00',
        ]);

        $milestones = [];

        foreach ($defaults as $threshold => $amount) {
            $threshold = (int) $threshold;
            $milestones[$threshold] = $this->decimal('milestone_'.$threshold, (string) $amount);
        }

        ksort($milestones);

        return $milestones;
    }

    protected function raw(string $key, string $default): string
    {
        $value = $this->settings->get($key);

        return $value !== null && trim($value) !== '' ? trim($value) : $default;
    }

    protected function decimal(string $key, string $default): string
    {
        try {
            return Money::normalizeDecimal($this->raw($key, $default));
        } catch (\InvalidArgumentException) {
            return Money::normalizeDecimal($default);
        }
    }
}
