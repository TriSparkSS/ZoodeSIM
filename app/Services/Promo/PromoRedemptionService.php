<?php

namespace App\Services\Promo;

use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Fraud\Contracts\DeviceFraudServiceInterface;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use App\Services\Promo\Contracts\PromoAuditLoggerInterface;
use App\Services\Referral\Contracts\ReferralMilestoneServiceInterface;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

class PromoRedemptionService
{
    public function __construct(
        protected WalletLedgerServiceInterface $ledger,
        protected ReferralMilestoneServiceInterface $milestones,
        protected DeviceFraudServiceInterface $fraud,
        protected NotificationDispatcherInterface $notifications,
        protected PromoAuditLoggerInterface $audit,
    ) {}

    public function redeem(User $user, PromoCode $promo): PromoUsage
    {
        $locked = PromoCode::query()
            ->whereKey($promo->id)
            ->lockForUpdate()
            ->first();

        if ($locked === null) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.invalid'),
            ]);
        }

        if (! $locked->is_active) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.inactive'),
            ]);
        }

        if ($locked->isExpired()) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.expired'),
            ]);
        }

        if ($locked->isExhausted()) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.exhausted'),
            ]);
        }

        $partner = Partner::query()
            ->whereKey($locked->partner_id)
            ->lockForUpdate()
            ->first();

        if ($partner === null || ! $partner->isActive()) {
            throw ValidationException::withMessages([
                'referral_code' => __('api.promo.partner_inactive'),
            ]);
        }

        if (is_string($user->device_id) && $user->device_id !== '') {
            $this->fraud->assertDeviceCanRedeemPromo($user->device_id, $user->id);
        }

        $bonusType = $locked->bonus_type === PromoCode::BONUS_TYPE_USD
            ? PromoCode::BONUS_TYPE_USD
            : PromoCode::BONUS_TYPE_MB;
        $bonusAmount = $bonusType === PromoCode::BONUS_TYPE_USD
            ? (string) ($locked->bonus_amount ?? '0.00')
            : number_format((int) $locked->bonus_mb, 2, '.', '');
        $bonusMb = $bonusType === PromoCode::BONUS_TYPE_USD ? 0 : (int) $locked->bonus_mb;

        $usage = PromoUsage::query()->create([
            'promo_code_id' => $locked->id,
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'bonus_mb_given' => $bonusMb,
            'bonus_type' => $bonusType,
            'bonus_amount' => $bonusAmount,
            'partner_reward' => $locked->partner_reward,
            'used_at' => now(),
            'device_id' => $user->device_id,
            'ip_address' => $user->registration_ip,
        ]);

        $locked->increment('usage_count');

        if ($bonusType === PromoCode::BONUS_TYPE_USD) {
            $usd = Money::fromDecimal($bonusAmount, (string) config('pricing.currency', 'USD'));

            if ($usd->cents > 0) {
                $this->ledger->credit(
                    $user,
                    $usd,
                    Transaction::CATEGORY_PROMO_BONUS,
                    'promo_usage',
                    $usage->id,
                    'Promo registration bonus',
                    promoCodeId: $locked->id,
                    meta: [
                        'promo_code' => $locked->code,
                        'bonus_type' => $bonusType,
                        'bonus_amount' => $usd->toDecimal(),
                        'partner_id' => $partner->id,
                    ],
                );
            }
        } elseif ($bonusMb > 0) {
            $this->ledger->credit(
                $user,
                Money::fromDecimal((string) $bonusMb, 'MB'),
                Transaction::CATEGORY_PROMO_BONUS,
                'promo_usage',
                $usage->id,
                'Promo registration bonus',
                promoCodeId: $locked->id,
                meta: [
                    'promo_code' => $locked->code,
                    'bonus_type' => $bonusType,
                    'bonus_mb' => $bonusMb,
                    'partner_id' => $partner->id,
                ],
            );
        }

        $reward = Money::fromDecimal((string) $locked->partner_reward, (string) config('pricing.currency', 'USD'));

        if ($reward->cents > 0) {
            $this->ledger->credit(
                $partner,
                $reward,
                Transaction::CATEGORY_PROMO_REWARD,
                'promo_usage',
                $usage->id,
                'Referral registration commission',
                countsAsEarning: true,
                promoCodeId: $locked->id,
                meta: [
                    'promo_code' => $locked->code,
                    'commission' => $reward->toDecimal(),
                    'user_id' => $user->id,
                    'bonus_type' => $bonusType,
                    'bonus_mb' => $bonusMb,
                    'bonus_amount' => $bonusAmount,
                ],
            );
        }

        $this->milestones->awardIfDue($partner->fresh());
        $this->notifications->referralRegistered($partner->fresh(), $user->fresh(), $usage);
        $this->audit->redeemed($locked, $user, $usage);

        return $usage;
    }
}
