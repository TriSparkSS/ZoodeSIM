<?php

namespace App\Services\Partner;

use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\Withdrawal;
use Illuminate\Support\Collection;

/**
 * Partner-portal read models: always scoped to the authenticated partner.
 */
class PartnerPortalDataService
{
    public function stats(Partner $partner): array
    {
        $promoCodes = $partner->promoCodes()->get();
        $registrations = (int) $promoCodes->sum('usage_count');
        $activePromo = $promoCodes
            ->sortByDesc('created_at')
            ->first(fn (PromoCode $promo) => $promo->isCurrentlyUsable())
            ?? $promoCodes->sortByDesc('created_at')->first();

        return [
            'registrations' => $registrations,
            'registrations_change' => '',
            'earnings' => (float) $partner->total_earned,
            'earnings_change' => '',
            'active_users' => $registrations,
            'conversion' => '—',
            'available_withdrawal' => (float) $partner->balance,
            'promo_code' => $activePromo?->code ?? '—',
            'promo_bonus' => $activePromo ? $activePromo->bonus_mb.' MB' : '—',
            'promo_reward' => $activePromo ? '$'.number_format((float) $activePromo->partner_reward, 2) : '—',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function promoCodes(Partner $partner): array
    {
        return $partner->promoCodes()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PromoCode $promo) => [
                'id' => $promo->id,
                'code' => $promo->code,
                'uses' => (int) $promo->usage_count,
                'bonus' => $promo->bonus_mb.' MB',
                'earnings' => round((int) $promo->usage_count * (float) $promo->partner_reward, 2),
                'status' => $promo->lifecycleStatus(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function registrations(Partner $partner): array
    {
        return PromoUsage::query()
            ->where('partner_id', $partner->id)
            ->with('promoCode')
            ->orderByDesc('used_at')
            ->get()
            ->map(function (PromoUsage $usage) {
                $code = $usage->promoCode?->code ?? '—';

                return [
                    'id' => $usage->id,
                    'initial' => 'U',
                    'name' => 'User '.substr((string) $usage->user_id, 0, 8),
                    'time' => $usage->used_at?->diffForHumans() ?? '',
                    'code' => $code,
                    'bonus' => $usage->bonus_mb_given.' MB',
                    'gradient' => 'from-brand-cyan to-brand-purple',
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function withdrawals(Partner $partner): array
    {
        return $partner->withdrawals()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Withdrawal $withdrawal) => [
                'id' => $withdrawal->id,
                'amount' => (float) $withdrawal->amount,
                'method' => $withdrawal->method,
                'status' => $withdrawal->status,
                'date' => $withdrawal->created_at?->format('Y-m-d'),
            ])
            ->all();
    }

    public function findOwnPromoCode(Partner $partner, string $promoCodeId): ?PromoCode
    {
        return $partner->promoCodes()->whereKey($promoCodeId)->first();
    }

    public function findOwnWithdrawal(Partner $partner, string $withdrawalId): ?Withdrawal
    {
        return $partner->withdrawals()->whereKey($withdrawalId)->first();
    }
}
