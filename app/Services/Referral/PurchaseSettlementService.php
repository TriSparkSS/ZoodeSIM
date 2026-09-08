<?php

namespace App\Services\Referral;

use App\Models\EsimOrder;
use App\Models\PromoUsage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use App\Services\Referral\Contracts\PurchaseSettlementServiceInterface;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class PurchaseSettlementService implements PurchaseSettlementServiceInterface
{
    public function __construct(
        protected ReferralProgramSettings $settings,
        protected WalletLedgerServiceInterface $ledger,
        protected NotificationDispatcherInterface $notifications,
    ) {}

    public function settle(EsimOrder $order): void
    {
        if ($order->payment_status !== EsimOrder::PAYMENT_PAID) {
            return;
        }

        DB::transaction(function () use ($order) {
            $fresh = EsimOrder::query()->whereKey($order->id)->lockForUpdate()->first();

            if ($fresh === null || $fresh->payment_status !== EsimOrder::PAYMENT_PAID) {
                return;
            }

            $user = User::query()->whereKey($fresh->user_id)->lockForUpdate()->first();

            if ($user === null) {
                return;
            }

            $charged = $this->chargedAmount($fresh);

            $this->creditCashback($user, $fresh, $charged);
            $this->creditPartnerCommission($user, $fresh, $charged);
        });
    }

    protected function chargedAmount(EsimOrder $order): Money
    {
        $currency = (string) ($order->currency ?: config('pricing.currency', 'USD'));
        $amount = $order->charged_amount ?? $order->customer_price;

        return Money::fromDecimal((string) $amount, $currency);
    }

    protected function creditCashback(User $user, EsimOrder $order, Money $charged): void
    {
        $minimum = $this->settings->cashbackMinAmount();

        if ($charged->currency !== $minimum->currency || $charged->cents <= $minimum->cents) {
            return;
        }

        if ($this->alreadySettled($order, Transaction::CATEGORY_PURCHASE_CASHBACK)) {
            return;
        }

        $cashback = $charged->percentageOf($this->settings->cashbackPercent());

        if ($cashback->cents < 1) {
            return;
        }

        $this->ledger->credit(
            $user,
            $cashback,
            Transaction::CATEGORY_PURCHASE_CASHBACK,
            'esim_order',
            $order->id,
            'Purchase cashback',
            meta: [
                'charged_amount' => $charged->toDecimal(),
                'cashback_percent' => $this->settings->cashbackPercent(),
            ],
        );

        $this->notifications->purchaseCashback($user->fresh(), $order, $cashback);
    }

    protected function creditPartnerCommission(User $user, EsimOrder $order, Money $charged): void
    {
        if ($this->alreadySettled($order, Transaction::CATEGORY_PURCHASE_COMMISSION)) {
            return;
        }

        $usage = PromoUsage::query()
            ->with(['partner', 'promoCode'])
            ->where('user_id', $user->id)
            ->first();

        $partner = $usage?->partner;

        if ($partner === null || ! $partner->isActive()) {
            return;
        }

        $priorPaid = EsimOrder::query()
            ->where('user_id', $user->id)
            ->where('payment_status', EsimOrder::PAYMENT_PAID)
            ->where('id', '!=', $order->id)
            ->count();

        $percent = $priorPaid === 0
            ? $this->settings->firstPurchaseCommissionPercent()
            : $this->settings->subsequentPurchaseCommissionPercent();

        $commission = $charged->percentageOf($percent);

        if ($commission->cents < 1) {
            return;
        }

        $this->ledger->credit(
            $partner,
            $commission,
            Transaction::CATEGORY_PURCHASE_COMMISSION,
            'esim_order',
            $order->id,
            $priorPaid === 0 ? 'First purchase commission' : 'Repeat purchase commission',
            countsAsEarning: true,
            promoCodeId: $usage->promo_code_id,
            meta: [
                'charged_amount' => $charged->toDecimal(),
                'commission_percent' => $percent,
                'user_id' => $user->id,
                'promo_code' => $usage->promoCode?->code,
            ],
        );

        $this->notifications->purchaseCommission($partner->fresh(), $user, $order, $commission);
    }

    protected function alreadySettled(EsimOrder $order, string $category): bool
    {
        return Transaction::query()
            ->where('reference_type', 'esim_order')
            ->where('reference_id', $order->id)
            ->where('category', $category)
            ->exists();
    }
}
