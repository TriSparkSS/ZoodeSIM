<?php

namespace App\Services\Partner;

use App\Models\Partner;
use App\Models\PromoCode;
use App\Models\PromoUsage;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\Promo\PromoLadderService;
use App\Services\Referral\ReferralProgramSettings;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Partner-portal read models: always scoped to the authenticated partner.
 */
class PartnerPortalDataService
{
    /**
     * @var list<string>
     */
    private const DAY_LABELS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    /**
     * @var list<string>
     */
    private const EARNING_PERIODS = ['today', 'week', 'month', 'all'];

    public function __construct(
        protected ReferralProgramSettings $program,
        protected PromoLadderService $ladder,
    ) {}

    public function stats(Partner $partner): array
    {
        $promoCodes = $partner->promoCodes()->get();
        $registrations = (int) $promoCodes->sum('usage_count');
        $activePromo = $promoCodes
            ->sortByDesc('created_at')
            ->first(fn (PromoCode $promo) => $promo->isCurrentlyUsable());

        $withdrawn = (float) $partner->withdrawals()
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'registrations' => $registrations,
            'registrations_change' => '',
            'earnings' => (float) $partner->total_earned,
            'earnings_change' => '',
            'active_users' => $registrations,
            'conversion' => '—',
            'available_withdrawal' => (float) $partner->balance,
            'withdrawn' => $withdrawn,
            'promo_code' => $activePromo?->code ?? '—',
            'promo_bonus' => $activePromo ? $activePromo->userBonusLabel() : '—',
            'promo_reward' => $activePromo
                ? '$'.number_format((float) $activePromo->partner_reward, 2)
                : '$'.$this->program->defaultRegistrationReward(),
            'promo_reward_raw' => $activePromo
                ? (float) $activePromo->partner_reward
                : (float) $this->program->defaultRegistrationReward(),
            'purchase_commission_rate' => $this->program->percentLabel($this->program->firstPurchaseCommissionPercent()),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function promoCodes(Partner $partner): array
    {
        $total = $this->ladder->referredUserCount($partner->id);

        return $partner->promoCodes()
            ->orderByDesc('created_at')
            ->get()
            ->map(function (PromoCode $promo) use ($total) {
                $locked = $promo->isLocked();

                return [
                    'id' => $promo->id,
                    'code' => $locked ? '••••••••' : $promo->code,
                    'uses' => (int) $promo->usage_count,
                    'progress' => $locked
                        ? __('partner.promo_codes.progress_users', [
                            'current' => $total,
                            'required' => (int) $promo->unlock_requirement,
                        ])
                        : null,
                    'uses_label' => $locked
                        ? __('partner.promo_codes.progress_users', [
                            'current' => $total,
                            'required' => (int) $promo->unlock_requirement,
                        ])
                        : number_format((int) $promo->usage_count),
                    'is_locked' => $locked,
                    'bonus' => $promo->userBonusLabel(),
                    'earnings' => round((int) $promo->usage_count * (float) $promo->partner_reward, 2),
                    'status' => $promo->lifecycleStatus(),
                ];
            })
            ->all();
    }

    /**
     * Last N calendar days of promo registrations for the partner chart.
     *
     * @return array<int, array{label: string, count: int, value: int}>
     */
    public function dailyRegistrations(Partner $partner, int $days = 7): array
    {
        $days = max(1, $days);
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $counts = PromoUsage::query()
            ->where('partner_id', $partner->id)
            ->whereBetween('used_at', [$start, $end])
            ->get(['used_at'])
            ->countBy(fn (PromoUsage $usage) => $usage->used_at?->toDateString());

        $max = (int) ($counts->max() ?: 0);
        $bars = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset);
            $count = (int) $counts->get($date->toDateString(), 0);

            $bars[] = [
                'label' => self::DAY_LABELS[(int) $date->dayOfWeek],
                'count' => $count,
                'value' => $max > 0 ? (int) round(($count / $max) * 100) : 0,
            ];
        }

        return $bars;
    }

    public function registrationsThisMonth(Partner $partner): int
    {
        return PromoUsage::query()
            ->where('partner_id', $partner->id)
            ->whereBetween('used_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function registrations(
        Partner $partner,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): array {
        $from = $this->parseDate($dateFrom)?->startOfDay();
        $to = $this->parseDate($dateTo)?->endOfDay();

        return PromoUsage::query()
            ->where('partner_id', $partner->id)
            ->with(['promoCode', 'user'])
            ->when($from, fn (Builder $query) => $query->where('used_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('used_at', '<=', $to))
            ->when($search !== null && trim($search) !== '', function (Builder $query) use ($search) {
                $term = '%'.addcslashes(trim($search), '%_\\').'%';

                $query->where(function (Builder $inner) use ($term) {
                    $inner->whereHas('user', fn (Builder $users) => $users->where('name', 'like', $term))
                        ->orWhereHas('promoCode', fn (Builder $codes) => $codes->where('code', 'like', $term));
                });
            })
            ->orderByDesc('used_at')
            ->get()
            ->map(function (PromoUsage $usage) {
                $name = trim((string) ($usage->user?->name ?? '')) ?: __('partner.registrations.unknown_user');
                $initial = mb_strtoupper(mb_substr($name, 0, 1));

                return [
                    'id' => $usage->id,
                    'initial' => $initial !== '' ? $initial : 'U',
                    'name' => $name,
                    'time' => $usage->used_at?->diffForHumans() ?? '',
                    'date' => $usage->used_at?->format('Y-m-d') ?? '',
                    'status' => 'registered',
                    'code' => $usage->promoCode?->code ?? '—',
                    'bonus' => $usage->userBonusLabel(),
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
                'date' => $withdrawal->requested_at?->format('Y-m-d') ?? $withdrawal->created_at?->format('Y-m-d'),
            ])
            ->all();
    }

    /**
     * @param  'today'|'week'|'month'|'all'|string  $period
     * @return array<int, array<string, mixed>>
     */
    public function earningsHistory(Partner $partner, string $period = 'all'): array
    {
        $period = in_array($period, self::EARNING_PERIODS, true) ? $period : 'all';

        return $partner->transactions()
            ->where('type', Transaction::TYPE_CREDIT)
            ->whereIn('category', [
                Transaction::CATEGORY_PROMO_REWARD,
                Transaction::CATEGORY_PURCHASE_COMMISSION,
                Transaction::CATEGORY_REFERRAL_MILESTONE,
            ])
            ->when($period !== 'all', function ($query) use ($period) {
                $query->where('created_at', '>=', $this->periodStart($period));
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function (Transaction $transaction) {
                $type = match ($transaction->category) {
                    Transaction::CATEGORY_PURCHASE_COMMISSION => 'purchase',
                    Transaction::CATEGORY_REFERRAL_MILESTONE => 'milestone',
                    default => 'registration',
                };

                $fallback = match ($type) {
                    'purchase' => __('partner.earnings.type_purchase'),
                    'milestone' => __('partner.earnings.type_milestone'),
                    default => __('partner.earnings.type_registration'),
                };

                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'description' => $transaction->description ?: $fallback,
                    'type' => $type,
                    'amount' => (float) $transaction->amount,
                    'date' => $transaction->created_at?->format('Y-m-d'),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function walletTransactions(Partner $partner, int $limit = 50): array
    {
        return $partner->transactions()
            ->with('promoCode:id,code')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'transaction_id' => $transaction->transaction_id,
                'type' => $transaction->type,
                'category' => $transaction->category,
                'amount' => (float) $transaction->amount,
                'balance_before' => (float) $transaction->balance_before,
                'balance_after' => (float) $transaction->balance_after,
                'currency' => $transaction->currency,
                'promo' => $transaction->promoCode?->code ?? ($transaction->meta['promo_code'] ?? null),
                'description' => $transaction->description,
                'status' => $transaction->status,
                'date' => $transaction->created_at?->format('Y-m-d H:i'),
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

    protected function parseDate(?string $value): ?CarbonInterface
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($value));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  'today'|'week'|'month'  $period
     */
    protected function periodStart(string $period): CarbonInterface
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            default => now()->startOfMonth(),
        };
    }
}
