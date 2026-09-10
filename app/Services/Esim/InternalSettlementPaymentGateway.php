<?php

namespace App\Services\Esim;

use App\DataTransferObjects\EsimPaymentResult;
use App\Models\EsimOrder;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Esim\Contracts\EsimPaymentGatewayInterface;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class InternalSettlementPaymentGateway implements EsimPaymentGatewayInterface
{
    public function __construct(
        protected WalletLedgerServiceInterface $ledger,
    ) {}

    public function settle(EsimOrder $order): EsimPaymentResult
    {
        try {
            $amount = Money::fromDecimal(
                (string) ($order->charged_amount ?? $order->customer_price),
                (string) ($order->currency ?: config('pricing.currency', 'USD')),
            );
        } catch (InvalidArgumentException) {
            return EsimPaymentResult::failed('payment_failed');
        }

        Log::info('eSIM payment settling', [
            'order_id' => $order->id,
            'amount_cents' => $amount->cents,
            'currency' => $amount->currency,
        ]);

        if ($amount->cents < 1) {
            return EsimPaymentResult::paid();
        }

        return DB::transaction(function () use ($order, $amount) {
            $user = User::query()->whereKey($order->user_id)->lockForUpdate()->first();

            if ($user === null) {
                return EsimPaymentResult::failed('payment_failed');
            }

            $alreadyDebited = Transaction::query()
                ->where('transactable_type', $user->getMorphClass())
                ->where('transactable_id', $user->id)
                ->where('category', Transaction::CATEGORY_ESIM_PURCHASE)
                ->where('reference_type', 'esim_order')
                ->where('reference_id', $order->id)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->exists();

            if ($alreadyDebited) {
                return EsimPaymentResult::paid();
            }

            $balance = Money::fromDecimal((string) $user->balance, $amount->currency);

            if (! $balance->greaterThanOrEqual($amount)) {
                return EsimPaymentResult::failed('insufficient_balance');
            }

            $this->ledger->debit(
                $user,
                $amount,
                Transaction::CATEGORY_ESIM_PURCHASE,
                'esim_order',
                (string) $order->id,
                __('api.esim.purchased'),
            );

            return EsimPaymentResult::paid();
        });
    }
}
