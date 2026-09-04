<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithToast;
use App\Services\Admin\AdminStatisticsService;
use Livewire\Component;

class Statistics extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public function render(AdminStatisticsService $statistics)
    {
        $monthlyTrend = $statistics->monthlyTrend();
        $maxEarnings = collect($monthlyTrend)->max('earnings') ?: 1;
        $maxRegistrations = collect($monthlyTrend)->max('registrations') ?: 1;

        return $this->withLocalizedTitle(view('livewire.admin.statistics', [
            'stats' => $statistics->summary(),
            'monthlyTrend' => $monthlyTrend,
            'maxEarnings' => $maxEarnings,
            'maxRegistrations' => $maxRegistrations,
            'topPartners' => $statistics->topPartners(),
            'recentApplications' => $statistics->recentApplications(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.statistics')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.nav.statistics');
    }
}
