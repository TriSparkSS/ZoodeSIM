<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\PromoAuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class PromoAuditLogs extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithPagination;

    public string $search = '';

    public string $action = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = PromoAuditLog::query()
            ->with(['partner:id,name', 'promoCode:id,code'])
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->when($this->search !== '', function ($query) {
                $term = '%'.addcslashes(trim($this->search), '%_\\').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', $term)
                        ->orWhereHas('partner', fn ($partners) => $partners->where('name', 'like', $term));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->withLocalizedTitle(view('livewire.admin.promo-audit-logs', [
            'logs' => $logs,
            'actions' => PromoAuditLog::actions(),
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.promo_audit')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.promo_audit.title');
    }
}
