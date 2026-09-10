<?php

namespace App\Services\User;

use App\DataTransferObjects\AdjustUserBalanceData;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Auth\AuthActivityLogger;
use App\Services\User\Contracts\UserBalanceAdjustmentServiceInterface;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class UserBalanceAdjustmentService implements UserBalanceAdjustmentServiceInterface
{
    public function __construct(
        protected WalletLedgerServiceInterface $ledger,
        protected AuthActivityLogger $activity,
    ) {}

    public function adjustByAdmin(User $user, Admin $admin, AdjustUserBalanceData $data): Transaction
    {
        $note = $data->trimmedNote();

        if ($note === '') {
            throw ValidationException::withMessages([
                'walletNote' => [__('admin.users.wallet.validation.note_required')],
            ]);
        }

        $transaction = $this->apply(
            $user,
            $data,
            $data->isCredit() ? Transaction::CATEGORY_ADMIN_CREDIT : Transaction::CATEGORY_ADMIN_DEBIT,
            'admin',
            (string) $admin->id,
            $note,
            'walletAmount',
            __('admin.users.wallet.validation.insufficient_balance'),
        );

        $this->activity->log(
            'admin',
            $data->isCredit()
                ? AuthActivityLog::EVENT_USER_BALANCE_CREDITED
                : AuthActivityLog::EVENT_USER_BALANCE_DEBITED,
            $admin,
            meta: [
                'user_id' => $user->id,
                'amount' => $transaction->amount,
                'direction' => $data->direction,
                'balance_after' => $transaction->balance_after,
                'note' => $note,
            ],
        );

        return $transaction;
    }

    public function adjustByUser(User $user, AdjustUserBalanceData $data): Transaction
    {
        $note = $data->trimmedNote();

        return $this->apply(
            $user,
            $data,
            $data->isCredit() ? Transaction::CATEGORY_WALLET_CREDIT : Transaction::CATEGORY_WALLET_DEBIT,
            'user',
            (string) $user->id,
            $note !== '' ? $note : null,
            'amount',
            __('api.wallet.insufficient_balance'),
        );
    }

    protected function apply(
        User $user,
        AdjustUserBalanceData $data,
        string $category,
        string $referenceType,
        string $referenceId,
        ?string $description,
        string $amountErrorKey,
        string $insufficientMessage,
    ): Transaction {
        if (! in_array($data->direction, ['credit', 'debit'], true)) {
            throw ValidationException::withMessages([
                $amountErrorKey === 'walletAmount' ? 'walletDirection' : 'direction' => [
                    $amountErrorKey === 'walletAmount'
                        ? __('admin.users.wallet.validation.direction_invalid')
                        : __('api.wallet.direction_invalid'),
                ],
            ]);
        }

        try {
            $amount = Money::fromDecimal($data->amount, (string) config('pricing.currency', 'USD'));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                $amountErrorKey => [
                    $amountErrorKey === 'walletAmount'
                        ? __('admin.users.wallet.validation.amount_format')
                        : __('api.wallet.amount_format'),
                ],
            ]);
        }

        if ($amount->cents < 1) {
            throw ValidationException::withMessages([
                $amountErrorKey => [
                    $amountErrorKey === 'walletAmount'
                        ? __('admin.users.wallet.validation.amount_required')
                        : __('api.wallet.amount_required'),
                ],
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $data,
            $amount,
            $category,
            $referenceType,
            $referenceId,
            $description,
            $amountErrorKey,
            $insufficientMessage,
        ) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($locked === null) {
                throw ValidationException::withMessages([
                    $amountErrorKey => [__('admin.users.wallet.validation.user_missing')],
                ]);
            }

            if (! $data->isCredit()) {
                $balance = Money::fromDecimal((string) $locked->balance, $amount->currency);

                if (! $balance->greaterThanOrEqual($amount)) {
                    throw ValidationException::withMessages([
                        $amountErrorKey => [$insufficientMessage],
                    ]);
                }

                return $this->ledger->debit(
                    $locked,
                    $amount,
                    $category,
                    $referenceType,
                    $referenceId,
                    $description,
                );
            }

            return $this->ledger->credit(
                $locked,
                $amount,
                $category,
                $referenceType,
                $referenceId,
                $description,
            );
        });
    }
}
