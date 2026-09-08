<?php

namespace App\Services\Wallet;

use App\Models\Partner;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Wallet\Contracts\TransactionIdGeneratorInterface;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletLedgerService implements WalletLedgerServiceInterface
{
    public function __construct(
        protected TransactionIdGeneratorInterface $ids,
    ) {}

    public function credit(
        User|Partner $owner,
        Money $amount,
        string $category,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null,
        bool $countsAsEarning = false,
        ?string $promoCodeId = null,
        array $meta = [],
    ): Transaction {
        return $this->record(
            $owner,
            Transaction::TYPE_CREDIT,
            $amount,
            $category,
            $referenceType,
            $referenceId,
            $description,
            $countsAsEarning,
            $promoCodeId,
            $meta,
        );
    }

    public function debit(
        User|Partner $owner,
        Money $amount,
        string $category,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null,
        ?string $promoCodeId = null,
        array $meta = [],
    ): Transaction {
        return $this->record(
            $owner,
            Transaction::TYPE_DEBIT,
            $amount,
            $category,
            $referenceType,
            $referenceId,
            $description,
            false,
            $promoCodeId,
            $meta,
        );
    }

    protected function record(
        User|Partner $owner,
        string $type,
        Money $amount,
        string $category,
        ?string $referenceType,
        ?string $referenceId,
        ?string $description,
        bool $countsAsEarning,
        ?string $promoCodeId,
        array $meta,
    ): Transaction {
        if ($amount->cents < 1) {
            throw new InvalidArgumentException('Transaction amount must be greater than zero.');
        }

        return DB::transaction(function () use (
            $owner,
            $type,
            $amount,
            $category,
            $referenceType,
            $referenceId,
            $description,
            $countsAsEarning,
            $promoCodeId,
            $meta,
        ) {
            [$before, $after] = $this->applyWalletChange($owner, $type, $amount, $countsAsEarning);

            return Transaction::query()->create([
                'transaction_id' => $this->ids->next(),
                'transactable_type' => $owner->getMorphClass(),
                'transactable_id' => $owner->getKey(),
                'type' => $type,
                'category' => $category,
                'amount' => $amount->toDecimal(),
                'balance_before' => $before,
                'balance_after' => $after,
                'currency' => $amount->currency,
                'status' => Transaction::STATUS_COMPLETED,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'promo_code_id' => $promoCodeId,
                'description' => $description,
                'meta' => $meta === [] ? null : $meta,
            ]);
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function applyWalletChange(User|Partner $owner, string $type, Money $amount, bool $countsAsEarning): array
    {
        $locked = $owner->newQuery()->whereKey($owner->getKey())->lockForUpdate()->firstOrFail();
        $owner->setRawAttributes($locked->getAttributes());
        $owner->exists = true;

        if ($owner instanceof User && $amount->currency === 'MB') {
            $current = (int) $owner->bonus_mb;
            $delta = (int) $amount->toDecimal();
            $next = $type === Transaction::TYPE_CREDIT ? $current + $delta : $current - $delta;

            if ($next < 0) {
                throw new InvalidArgumentException('Money subtraction would be negative.');
            }

            $owner->forceFill(['bonus_mb' => $next])->save();

            return [number_format($current, 2, '.', ''), number_format($next, 2, '.', '')];
        }

        $current = Money::fromDecimal((string) $owner->balance, $amount->currency);
        $next = $type === Transaction::TYPE_CREDIT
            ? $current->add($amount)
            : $current->subtract($amount);

        $owner->forceFill(['balance' => $next->toDecimal()]);

        if ($countsAsEarning && $owner instanceof Partner) {
            $earned = Money::fromDecimal((string) $owner->total_earned, $amount->currency)->add($amount);
            $owner->forceFill(['total_earned' => $earned->toDecimal()]);
        }

        $owner->save();

        return [$current->toDecimal(), $next->toDecimal()];
    }
}
