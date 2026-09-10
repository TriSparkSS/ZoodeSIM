<?php

namespace App\Livewire\Admin;

use App\DataTransferObjects\ResellPortalOrderList;
use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\EsimOrder;
use App\Services\Esim\EsimOrderQueryService;
use App\Services\ResellPortal\ResellPortalOrderListService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithPagination;

    #[Url]
    public string $source = 'portal';

    #[Url]
    public string $clientId = '';

    #[Url]
    public string $user = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $paymentStatus = '';

    #[Url]
    public string $packageCode = '';

    #[Url]
    public string $location = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    protected EsimOrderQueryService $orders;

    protected ResellPortalOrderListService $liveOrders;

    public function boot(EsimOrderQueryService $orders, ResellPortalOrderListService $liveOrders): void
    {
        $this->orders = $orders;
        $this->liveOrders = $liveOrders;
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
            'clientId',
            'user',
            'status',
            'paymentStatus',
            'packageCode',
            'location',
            'dateFrom',
            'dateTo',
        ]);
        $this->resetPage();
    }

    public function isLive(): bool
    {
        return $this->source === 'live';
    }

    /**
     * @return array<string, string>
     */
    protected function filters(): array
    {
        return [
            'client_id' => $this->clientId,
            'user' => $this->user,
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
            'package_code' => $this->packageCode,
            'location' => $this->location,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function providerFilters(): array
    {
        return [
            'client_id' => $this->clientId,
            'status' => $this->status,
            'package_code' => $this->packageCode,
            'location' => $this->location,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    public function render()
    {
        $live = $this->isLive()
            ? $this->liveOrders->list($this->providerFilters())
            : new ResellPortalOrderList(available: true, rows: []);

        return $this->withLocalizedTitle(view('livewire.admin.orders', [
            'rows' => $this->isLive()
                ? collect()
                : $this->orders->filteredQuery($this->filters())
                    ->with('user')
                    ->orderByDesc('created_at')
                    ->paginate(20),
            'liveRows' => $live->rows,
            'liveAvailable' => $live->available,
            'statuses' => EsimOrder::statuses(),
            'paymentStatuses' => EsimOrder::paymentStatuses(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.orders')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.orders');
    }
}
