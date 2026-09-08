<?php

namespace App\Services\Partner;

use App\DataTransferObjects\AdjustPartnerBalanceData;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\Partner;
use App\Models\Transaction;
use App\Services\Auth\AuthActivityLogger;
use App\Services\Partner\Contracts\PartnerBalanceAdjustmentServiceInterface;
use App\Services\Partner\Contracts\PartnerLedgerServiceInterface;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PartnerBalanceAdjustmentService implements PartnerBalanceAdjustmentServiceInterface
{
    public function __construct(
        protected PartnerLedgerServiceInterface $ledger,
        protected AuthActivityLogger $activity,
    ) {}

    public function adjust(Partner $partner, Admin $admin, AdjustPartnerBalanceData $data): Transaction
    {
        if (! in_array($data->direction, ['credit', 'debit'], true)) {
            throw ValidationException::withMessages([
                'walletAmount' => [__('admin.partners.wallet.validation.direction_invalid')],
            ]);
        }

        try {
            $amount = Money::fromDecimal($data->amount, (string) config('pricing.currency', 'USD'));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'walletAmount' => [__('admin.partners.wallet.validation.amount_format')],
            ]);
        }

        if ($amount->cents < 1) {
            throw ValidationException::withMessages([
                'walletAmount' => [__('admin.partners.wallet.validation.amount_required')],
            ]);
        }

        $note = trim($data->note);

        if ($note === '') {
            throw ValidationException::withMessages([
                'walletNote' => [__('admin.partners.wallet.validation.note_required')],
            ]);
        }

        return DB::transaction(function () use ($partner, $admin, $data, $amount, $note) {
            $locked = Partner::query()->whereKey($partner->id)->lockForUpdate()->first();

            if ($locked === null) {
                throw ValidationException::withMessages([
                    'walletAmount' => [__('admin.partners.wallet.validation.partner_missing')],
                ]);
            }

            if ($data->isCredit()) {
                $transaction = $this->ledger->credit(
                    $locked,
                    $amount,
                    Transaction::CATEGORY_ADMIN_CREDIT,
                    'admin',
                    (string) $admin->id,
                    $note,
                );

                $event = AuthActivityLog::EVENT_PARTNER_BALANCE_CREDITED;
            } else {
                $balance = Money::fromDecimal((string) $locked->balance, $amount->currency);

                if (! $balance->greaterThanOrEqual($amount)) {
                    throw ValidationException::withMessages([
                        'walletAmount' => [__('admin.partners.wallet.validation.insufficient_balance')],
                    ]);
                }

                $transaction = $this->ledger->debit(
                    $locked,
                    $amount,
                    Transaction::CATEGORY_ADMIN_DEBIT,
                    'admin',
                    (string) $admin->id,
                    $note,
                );

                $event = AuthActivityLog::EVENT_PARTNER_BALANCE_DEBITED;
            }

            $this->activity->log(
                'admin',
                $event,
                $admin,
                meta: [
                    'partner_id' => $locked->id,
                    'amount' => $amount->toDecimal(),
                    'direction' => $data->direction,
                    'balance_after' => $transaction->balance_after,
                    'note' => $note,
                ],
            );

            return $transaction;
        });
    }
}
