<?php

namespace App\Services\Partner;

use App\DataTransferObjects\CreateWithdrawalRequestData;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\Partner;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\Auth\AuthActivityLogger;
use App\Services\Content\ProgramSettingService;
use App\Services\Notifications\Contracts\NotificationDispatcherInterface;
use App\Services\Partner\Contracts\PartnerLedgerServiceInterface;
use App\Services\Partner\Contracts\PayoutIdentityServiceInterface;
use App\Services\Partner\Contracts\WithdrawalServiceInterface;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalService implements WithdrawalServiceInterface
{
    public function __construct(
        protected PartnerLedgerServiceInterface $ledger,
        protected ProgramSettingService $settings,
        protected AuthActivityLogger $activity,
        protected NotificationDispatcherInterface $notifications,
        protected PayoutIdentityServiceInterface $identity,
    ) {}

    public function request(Partner $partner, CreateWithdrawalRequestData $data): Withdrawal
    {
        $amount = $this->money($data->amount);
        $minimum = $this->money($this->minimumAmount());

        if (! $partner->isActive()) {
            throw ValidationException::withMessages([
                'amount' => [__('partner.earnings.validation.partner_inactive')],
            ]);
        }

        if (! in_array($data->method, Withdrawal::methods(), true)) {
            throw ValidationException::withMessages([
                'method' => [__('partner.earnings.validation.method_invalid')],
            ]);
        }

        if ($amount->cents < 1) {
            throw ValidationException::withMessages([
                'amount' => [__('partner.earnings.validation.amount_required')],
            ]);
        }

        if (! $amount->greaterThanOrEqual($minimum)) {
            throw ValidationException::withMessages([
                'amount' => [__('partner.earnings.validation.below_minimum', [
                    'amount' => $minimum->format(),
                ])],
            ]);
        }

        $this->identity->assertReferralIsNotPartner($partner, $data->payoutDetails);

        return DB::transaction(function () use ($partner, $data, $amount) {
            $locked = Partner::query()->whereKey($partner->id)->lockForUpdate()->first();

            if ($locked === null || ! $locked->isActive()) {
                throw ValidationException::withMessages([
                    'amount' => [__('partner.earnings.validation.partner_inactive')],
                ]);
            }

            $hasOpen = Withdrawal::query()
                ->where('partner_id', $locked->id)
                ->whereIn('status', [Withdrawal::STATUS_PENDING, Withdrawal::STATUS_PROCESSING])
                ->lockForUpdate()
                ->exists();

            if ($hasOpen) {
                throw ValidationException::withMessages([
                    'amount' => [__('partner.earnings.validation.pending_exists')],
                ]);
            }

            $balance = $this->money((string) $locked->balance);

            if (! $balance->greaterThanOrEqual($amount)) {
                throw ValidationException::withMessages([
                    'amount' => [__('partner.earnings.validation.insufficient_balance')],
                ]);
            }

            $withdrawal = Withdrawal::query()->create([
                'partner_id' => $locked->id,
                'amount' => $amount->toDecimal(),
                'method' => $data->method,
                'payout_details' => $data->payoutDetails,
                'status' => Withdrawal::STATUS_PROCESSING,
                'requested_at' => now(),
            ]);

            $this->ledger->debit(
                $locked,
                $amount,
                Transaction::CATEGORY_WITHDRAWAL_HOLD,
                'withdrawal',
                $withdrawal->id,
                'Withdrawal request hold',
            );

            $this->activity->log(
                'partner',
                AuthActivityLog::EVENT_WITHDRAWAL_REQUESTED,
                $locked,
                meta: [
                    'withdrawal_id' => $withdrawal->id,
                    'amount' => $amount->toDecimal(),
                    'method' => $data->method,
                ],
            );

            return $withdrawal->fresh();
        });
    }

    public function complete(Withdrawal $withdrawal, Admin $admin): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin) {
            $locked = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->first();

            if ($locked === null || ! $locked->isActionable()) {
                throw ValidationException::withMessages([
                    'withdrawal' => [__('admin.payouts.validation.not_pending')],
                ]);
            }

            $partner = Partner::query()->whereKey($locked->partner_id)->lockForUpdate()->first();

            if ($partner === null) {
                throw ValidationException::withMessages([
                    'withdrawal' => [__('admin.payouts.validation.not_pending')],
                ]);
            }

            $this->identity->assertReferralIsNotPartner($partner, $locked->payout_details);

            $locked->update([
                'status' => Withdrawal::STATUS_COMPLETED,
                'completed_at' => now(),
                'processed_by' => $admin->id,
                'rejected_at' => null,
            ]);

            $this->activity->log(
                'admin',
                AuthActivityLog::EVENT_WITHDRAWAL_COMPLETED,
                $admin,
                meta: [
                    'withdrawal_id' => $locked->id,
                    'partner_id' => $locked->partner_id,
                    'amount' => (string) $locked->amount,
                ],
            );

            $fresh = $locked->fresh('partner');

            if ($fresh?->partner) {
                $this->notifications->payoutProcessed($fresh->partner, $fresh);
            }

            return $fresh;
        });
    }

    public function reject(Withdrawal $withdrawal, Admin $admin, ?string $note = null): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $note) {
            $locked = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->first();

            if ($locked === null || ! $locked->isActionable()) {
                throw ValidationException::withMessages([
                    'withdrawal' => [__('admin.payouts.validation.not_pending')],
                ]);
            }

            $partner = Partner::query()->whereKey($locked->partner_id)->lockForUpdate()->first();

            if ($partner === null) {
                throw ValidationException::withMessages([
                    'withdrawal' => [__('admin.payouts.validation.not_pending')],
                ]);
            }

            $this->ledger->credit(
                $partner,
                $this->money((string) $locked->amount),
                Transaction::CATEGORY_WITHDRAWAL_REFUND,
                'withdrawal',
                $locked->id,
                'Withdrawal request rejected',
            );

            $locked->update([
                'status' => Withdrawal::STATUS_REJECTED,
                'rejected_at' => now(),
                'processed_by' => $admin->id,
                'admin_note' => $note,
            ]);

            $this->activity->log(
                'admin',
                AuthActivityLog::EVENT_WITHDRAWAL_REJECTED,
                $admin,
                meta: [
                    'withdrawal_id' => $locked->id,
                    'partner_id' => $locked->partner_id,
                    'amount' => (string) $locked->amount,
                ],
            );

            $fresh = $locked->fresh('partner');

            if ($fresh?->partner) {
                $this->notifications->payoutProcessed($fresh->partner, $fresh);
            }

            return $fresh;
        });
    }

    public function minimumAmount(): string
    {
        $raw = $this->settings->get('min_withdrawal', '50');

        try {
            return Money::normalizeDecimal((string) $raw);
        } catch (\InvalidArgumentException) {
            return '50.00';
        }
    }

    protected function money(string $amount): Money
    {
        return Money::fromDecimal($amount, (string) config('pricing.currency', 'USD'));
    }
}
