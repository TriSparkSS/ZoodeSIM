<?php

namespace App\Services\Admin;

use App\Models\Partner;
use App\Models\PartnerApplication;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\Withdrawal;

class AdminStatisticsService
{
    /**
     * @return array{
     *     total_partners: int,
     *     total_registrations: int,
     *     total_payouts: float,
     *     active_codes: int
     * }
     */
    public function summary(): array
    {
        $activeCodes = PromoCode::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (PromoCode $promo) => $promo->isCurrentlyUsable())
            ->count();

        return [
            'total_partners' => Partner::query()->where('status', 'active')->count(),
            'total_registrations' => (int) (PromoUsage::query()->count() ?: PromoCode::query()->sum('usage_count')),
            'total_payouts' => (float) Withdrawal::query()->where('status', 'completed')->sum('amount'),
            'active_codes' => $activeCodes,
        ];
    }

    /**
     * Last N calendar months of registrations and partner rewards.
     *
     * @return array<int, array{month: int, year: int, label: string, registrations: int, earnings: float}>
     */
    public function monthlyTrend(int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $usages = PromoUsage::query()
            ->where('used_at', '>=', $start)
            ->get(['used_at', 'partner_reward']);

        $grouped = $usages->groupBy(fn (PromoUsage $usage) => $usage->used_at?->format('Y-n') ?? '');

        $trend = [];

        for ($i = 0; $i < $months; $i++) {
            $date = $start->copy()->addMonths($i);
            $key = $date->format('Y-n');
            $bucket = $grouped->get($key, collect());

            $trend[] = [
                'month' => (int) $date->month,
                'year' => (int) $date->year,
                'label' => $date->translatedFormat('M'),
                'registrations' => $bucket->count(),
                'earnings' => (float) $bucket->sum('partner_reward'),
            ];
        }

        return $trend;
    }

    /**
     * @return array<int, array{id: string, name: string, email: string, promo: ?string, registrations: int, earnings: float}>
     */
    public function topPartners(int $limit = 5): array
    {
        return Partner::query()
            ->where('status', 'active')
            ->with(['promoCodes' => fn ($q) => $q->orderByDesc('created_at')])
            ->get()
            ->map(function (Partner $partner) {
                $registrations = (int) $partner->promoCodes->sum('usage_count');
                $activePromo = $partner->promoCodes
                    ->first(fn (PromoCode $promo) => $promo->isCurrentlyUsable())
                    ?? $partner->promoCodes->first();

                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'promo' => $activePromo?->code,
                    'registrations' => $registrations,
                    'earnings' => (float) $partner->total_earned,
                ];
            })
            ->sortByDesc('registrations')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentApplications(int $limit = 5): array
    {
        return PartnerApplication::query()
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (PartnerApplication $app) {
                return [
                    'id' => $app->id,
                    'name' => trim($app->first_name.' '.($app->last_name ?? '')) ?: $app->first_name,
                    'email' => $app->email,
                    'platforms' => $app->platforms ?? [],
                    'followers' => $app->followers,
                    'status' => $app->status,
                    'date' => $app->created_at?->format('Y-m-d'),
                ];
            })
            ->all();
    }
}
