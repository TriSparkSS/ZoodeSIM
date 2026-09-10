<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\Transaction;
use App\Services\Wallet\TransactionQueryService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Transactions extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithPagination;

    #[Url]
    public string $transactionId = '';

    #[Url]
    public string $user = '';

    #[Url]
    public string $partner = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $amountMin = '';

    #[Url]
    public string $amountMax = '';

    #[Url]
    public string $promo = '';

    protected TransactionQueryService $transactions;

    public function boot(TransactionQueryService $transactions): void
    {
        $this->transactions = $transactions;
    }

    public function updated(string $name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'transactionId',
            'user',
            'partner',
            'category',
            'type',
            'status',
            'dateFrom',
            'dateTo',
            'amountMin',
            'amountMax',
            'promo',
        ]);
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    protected function filters(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'user' => $this->user,
            'partner' => $this->partner,
            'category' => $this->category,
            'type' => $this->type,
            'status' => $this->status,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'amount_min' => $this->amountMin,
            'amount_max' => $this->amountMax,
            'promo' => $this->promo,
        ];
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.admin.transactions', [
            'rows' => $this->transactions->filteredQuery($this->filters())
                ->orderByDesc('created_at')
                ->paginate(20),
            'categories' => Transaction::categories(),
            'types' => Transaction::types(),
            'statuses' => Transaction::statuses(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.transactions')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.transactions');
    }
}
