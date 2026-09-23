<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\User;
use App\Models\UserReferral;
use Livewire\Component;
use Livewire\WithPagination;

class UserReferralHistory extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithPagination;

    public string $userId = '';

    public function mount(string $user): void
    {
        $this->userId = User::query()->withTrashed()->whereKey($user)->firstOrFail()->id;
    }

    public function render()
    {
        $subject = User::query()->withTrashed()->whereKey($this->userId)->firstOrFail();

        $referrals = UserReferral::query()
            ->with(['referred' => fn ($query) => $query->withTrashed()])
            ->where('referrer_id', $subject->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        $totalEarned = (float) UserReferral::query()
            ->where('referrer_id', $subject->id)
            ->sum('referrer_amount');

        return $this->withLocalizedTitle(view('livewire.admin.user-referral-history', [
            'subject' => $subject,
            'referrals' => $referrals,
            'totalEarned' => $totalEarned,
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.users.referrals_modal_title', [
                'name' => $subject->name,
            ])),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.users.referrals_modal_title', ['name' => $subject->name]);
    }
}
