<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ResolvesAuthenticatedAdmin;
use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Models\Withdrawal;
use App\Services\Partner\Contracts\WithdrawalServiceInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Payouts extends Component
{
    use ResolvesAuthenticatedAdmin;
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public bool $showRejectModal = false;

    public string $rejectingId = '';

    public string $rejectNote = '';

    public function completePayout(string $id, WithdrawalServiceInterface $withdrawals): void
    {
        $admin = $this->admin();
        $withdrawal = Withdrawal::query()->with('partner')->whereKey($id)->first();

        if ($withdrawal === null) {
            return;
        }

        Gate::forUser($admin)->authorize('complete', $withdrawal);

        try {
            $completed = $withdrawals->complete($withdrawal, $admin);
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.payouts.validation.not_pending'), 'error');

            return;
        }

        $this->toast(__('admin.payouts.completed_toast', [
            'name' => $completed->partner?->name ?? __('admin.partners.table_name'),
        ]));
    }

    public function openRejectModal(string $id): void
    {
        $this->resetValidation();
        $this->rejectingId = $id;
        $this->rejectNote = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->rejectingId = '';
        $this->rejectNote = '';
        $this->resetValidation();
    }

    public function rejectPayout(WithdrawalServiceInterface $withdrawals): void
    {
        $admin = $this->admin();
        $withdrawal = Withdrawal::query()->with('partner')->whereKey($this->rejectingId)->first();

        if ($withdrawal === null) {
            $this->closeRejectModal();

            return;
        }

        Gate::forUser($admin)->authorize('reject', $withdrawal);

        $validated = $this->validate([
            'rejectNote' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $rejected = $withdrawals->reject(
                $withdrawal,
                $admin,
                filled($validated['rejectNote'] ?? null) ? $validated['rejectNote'] : null,
            );
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?: __('admin.payouts.validation.not_pending'), 'error');

            return;
        }

        $this->closeRejectModal();
        $this->toast(__('admin.payouts.rejected_toast', [
            'name' => $rejected->partner?->name ?? __('admin.partners.table_name'),
        ]));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function payoutRows(): array
    {
        return Withdrawal::query()
            ->with('partner')
            ->orderByDesc('requested_at')
            ->get()
            ->map(fn (Withdrawal $withdrawal) => [
                'id' => $withdrawal->id,
                'partner' => $withdrawal->partner?->name,
                'amount' => (float) $withdrawal->amount,
                'method' => $withdrawal->method,
                'details' => $withdrawal->payout_details,
                'status' => $withdrawal->status,
                'badge' => $withdrawal->statusBadgeType(),
                'date' => $withdrawal->requested_at?->format('Y-m-d'),
                'is_pending' => $withdrawal->isActionable(),
            ])
            ->all();
    }

    /**
     * @return array{pending: float, completed_month: float}
     */
    protected function payoutStats(): array
    {
        $pending = (float) Withdrawal::query()
            ->whereIn('status', [Withdrawal::STATUS_PENDING, Withdrawal::STATUS_PROCESSING])
            ->sum('amount');

        $completedMonth = (float) Withdrawal::query()
            ->where('status', Withdrawal::STATUS_COMPLETED)
            ->where('completed_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return [
            'pending' => $pending,
            'completed_month' => $completedMonth,
        ];
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.payouts', [
            'payouts' => $this->payoutRows(),
            'stats' => $this->payoutStats(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.payouts')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.payouts');
    }
}
