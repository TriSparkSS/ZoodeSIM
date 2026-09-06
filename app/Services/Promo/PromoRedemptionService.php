<?php

namespace App\Services\Promo;

use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PromoRedemptionService
{
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

        $usage = PromoUsage::query()->create([
            'promo_code_id' => $locked->id,
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'bonus_mb_given' => (int) $locked->bonus_mb,
            'partner_reward' => $locked->partner_reward,
            'used_at' => now(),
        ]);

        $locked->increment('usage_count');
        $partner->increment('balance', (float) $locked->partner_reward);
        $partner->increment('total_earned', (float) $locked->partner_reward);

        return $usage;
    }
}
