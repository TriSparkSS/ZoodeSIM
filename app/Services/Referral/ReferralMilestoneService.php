<?php

namespace App\Services\Referral;

use App\Models\Partner;
use App\Models\PromoUsage;
use App\Models\Transaction;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use App\Services\Referral\Contracts\ReferralMilestoneServiceInterface;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;

class ReferralMilestoneService implements ReferralMilestoneServiceInterface
{
    public function __construct(
        protected ReferralProgramSettings $settings,
        protected WalletLedgerServiceInterface $ledger,
        protected NotificationDispatcherInterface $notifications,
    ) {}

    public function awardIfDue(Partner $partner): void
    {
        $count = PromoUsage::query()->where('partner_id', $partner->id)->count();
        $awarded = $this->awardedThresholds($partner);
        $currency = (string) config('pricing.currency', 'USD');

        foreach ($this->settings->milestones() as $threshold => $amount) {
            if ($count < $threshold || in_array($threshold, $awarded, true)) {
                continue;
            }

            $bonus = Money::fromDecimal($amount, $currency);

            if ($bonus->cents < 1) {
                continue;
            }

            $this->ledger->credit(
                $partner,
                $bonus,
                Transaction::CATEGORY_REFERRAL_MILESTONE,
                'referral_milestone',
                (string) $threshold,
                'Referral milestone bonus',
                countsAsEarning: true,
                meta: [
                    'threshold' => $threshold,
                    'referral_count' => $count,
                    'bonus' => $bonus->toDecimal(),
                ],
            );

            $this->notifications->milestoneReached($partner->fresh(), $threshold, $bonus);
        }
    }

    /**
     * @return list<int>
     */
    protected function awardedThresholds(Partner $partner): array
    {
        return $partner->transactions()
            ->where('category', Transaction::CATEGORY_REFERRAL_MILESTONE)
            ->get()
            ->map(fn (Transaction $transaction) => (int) ($transaction->meta['threshold'] ?? 0))
            ->filter()
            ->values()
            ->all();
    }
}
