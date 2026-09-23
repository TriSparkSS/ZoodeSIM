<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\Partner;
use App\Models\PromoUsage;
use Livewire\Component;
use Livewire\WithPagination;

class PartnerReferralHistory extends Component
{
    use WithAdminNavigation;
    use WithLocalizedTitle;
    use WithPagination;

    public string $partnerId = '';

    public function mount(string $partner): void
    {
        $this->partnerId = Partner::query()->whereKey($partner)->firstOrFail()->id;
    }

    public function render()
    {
        $subject = Partner::query()->whereKey($this->partnerId)->firstOrFail();

        $referrals = PromoUsage::query()
            ->with([
                'user' => fn ($query) => $query->withTrashed(),
                'promoCode:id,code',
            ])
            ->where('partner_id', $subject->id)
            ->orderByDesc('used_at')
            ->orderByDesc('id')
            ->paginate(20);

        $totalEarned = (float) PromoUsage::query()
            ->where('partner_id', $subject->id)
            ->sum('partner_reward');

        return $this->withLocalizedTitle(view('livewire.admin.partner-referral-history', [
            'subject' => $subject,
            'referrals' => $referrals,
            'totalEarned' => $totalEarned,
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.partners.referrals_modal_title', [
                'name' => $subject->name,
            ])),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
        ]), 'admin.partners.referrals_modal_title', ['name' => $subject->name]);
    }
}
