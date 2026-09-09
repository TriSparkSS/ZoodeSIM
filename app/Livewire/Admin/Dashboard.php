<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RefreshesResellPortalBalance;
use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Services\Admin\AdminStatisticsService;
use App\Services\ResellPortal\Contracts\ResellPortalBalanceServiceInterface;
use Livewire\Component;

class Dashboard extends Component
{
    use RefreshesResellPortalBalance;
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public function render(AdminStatisticsService $statistics, ResellPortalBalanceServiceInterface $balance)
    {
        return $this->withLocalizedTitle(view('livewire.admin.dashboard', [
            'stats' => $statistics->summary(),
            'providerBalance' => $balance->current(),
            'recentApplications' => $statistics->recentApplications(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.dashboard')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.dashboard.title');
    }
}
