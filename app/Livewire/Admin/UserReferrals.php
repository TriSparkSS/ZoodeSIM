<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\UserReferral;
use Livewire\Component;
use Livewire\WithPagination;

class UserReferrals extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $referrals = UserReferral::query()
            ->with(['referrer:id,name,email', 'referred:id,name,email'])
            ->when($this->search !== '', function ($query) {
                $term = '%'.addcslashes(trim($this->search), '%_\\').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->whereHas('referrer', function ($users) use ($term) {
                        $users->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('referral_code', 'like', $term);
                    })->orWhereHas('referred', function ($users) use ($term) {
                        $users->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->withLocalizedTitle(view('livewire.admin.user-referrals', [
            'referrals' => $referrals,
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.nav.user_referrals')),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.user_referrals.title');
    }
}
