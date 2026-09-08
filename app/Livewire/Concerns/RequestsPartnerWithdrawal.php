<?php

namespace App\Livewire\Concerns;

use App\DataTransferObjects\CreateWithdrawalRequestData;
use App\Models\Withdrawal;
use App\Services\Partner\Contracts\WithdrawalServiceInterface;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

trait RequestsPartnerWithdrawal
{
    public bool $showWithdrawModal = false;

    public string $withdrawAmount = '';

    public string $withdrawMethod = Withdrawal::METHOD_CARD;

    public string $withdrawDetails = '';

    public function openWithdrawModal(): void
    {
        $partner = $this->partner();
        $currency = (string) config('pricing.currency', 'USD');

        $this->resetValidation();
        $this->withdrawAmount = Money::fromDecimal((string) $partner->balance, $currency)->toDecimal();
        $this->withdrawMethod = $partner->payout_method ?: Withdrawal::METHOD_CARD;
        $this->withdrawDetails = $partner->payout_details ?: $partner->email;
        $this->showWithdrawModal = true;
    }

    public function closeWithdrawModal(): void
    {
        $this->showWithdrawModal = false;
        $this->resetValidation();
    }

    public function submitWithdrawal(WithdrawalServiceInterface $withdrawals): void
    {
        $partner = $this->partner();

        Gate::forUser($partner)->authorize('create', Withdrawal::class);

        $validated = $this->validate([
            'withdrawAmount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'withdrawMethod' => ['required', 'in:'.implode(',', Withdrawal::methods())],
            'withdrawDetails' => ['required', 'string', 'max:255'],
        ], [
            'withdrawAmount.required' => __('partner.earnings.validation.amount_required'),
            'withdrawAmount.regex' => __('partner.earnings.validation.amount_format'),
            'withdrawMethod.required' => __('partner.earnings.validation.method_invalid'),
            'withdrawMethod.in' => __('partner.earnings.validation.method_invalid'),
            'withdrawDetails.required' => __('partner.earnings.validation.details_required'),
        ]);

        try {
            $withdrawal = $withdrawals->request($partner, new CreateWithdrawalRequestData(
                amount: $validated['withdrawAmount'],
                method: $validated['withdrawMethod'],
                payoutDetails: $validated['withdrawDetails'],
            ));
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?: __('partner.earnings.validation.failed'), 'error');

            return;
        }

        $this->closeWithdrawModal();
        $this->toast(__('partner.dashboard.withdraw_requested', [
            'amount' => Money::fromDecimal((string) $withdrawal->amount, (string) config('pricing.currency', 'USD'))->format(),
        ]));
    }
}
