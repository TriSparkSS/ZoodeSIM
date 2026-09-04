<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithLocalizedTitle;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithToast;
use App\Models\Withdrawal;
use Livewire\Component;

class Payouts extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    /** @var array<int, array<string, mixed>> */
    public array $payouts = [];

    public function mount(): void
    {
        $this->payouts = Withdrawal::query()
            ->with('partner')
            ->orderByDesc('requested_at')
            ->get()
            ->map(function (Withdrawal $w) {
                return [
                    'id' => $w->id,
                    'partner' => $w->partner?->name,
                    'amount' => (float) $w->amount,
                    'method' => $w->method,
                    // UI expects: pending/approved/completed. Map processing -> approved.
                    'status' => $w->status === 'processing'
                        ? 'approved'
                        : ($w->status === 'failed' ? 'pending' : $w->status),
                    'date' => $w->requested_at?->format('Y-m-d'),
                ];
            })
            ->all();
    }

    public function processPayout(string $id): void
    {
        $withdrawal = Withdrawal::query()->with('partner')->whereKey($id)->first();

        if (! $withdrawal || $withdrawal->status !== 'pending') {
            return;
        }

        $withdrawal->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->toast(($withdrawal->partner?->name ?? __('admin.partners.table_name')).' — '.__('ui.status_completed'));

        $this->mount();
    }

    /**
     * @return array<string, float>
     */
    public function stats(): array
    {
        $pending = collect($this->payouts)
            ->where('status', 'pending')
            ->sum('amount');

        $completedMonth = collect($this->payouts)
            ->where('status', 'completed')
            ->where('date', '>=', now()->startOfMonth()->format('Y-m-d'))
            ->sum('amount');

        return [
            'pending' => $pending,
            'completed_month' => $completedMonth,
        ];
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.payouts', [
            'stats' => $this->stats(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.payouts')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.payouts');
    }
}
