<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\ResolvesAuthenticatedPartner;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithPartnerNavigation;
use App\Livewire\Concerns\WithToast;
use App\Services\Partner\PartnerPortalDataService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.partner')]
class Earnings extends Component
{
    use ResolvesAuthenticatedPartner;
    use WithPartnerNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public function requestWithdrawal(PartnerPortalDataService $portal): void
    {
        $amount = $portal->stats($this->partner())['available_withdrawal'];
        $this->toast(__('partner.dashboard.withdraw_requested', ['amount' => '$'.number_format($amount, 2)]));
    }

    public function render(PartnerPortalDataService $portal)
    {
        $partner = $this->partner();
        $stats = $portal->stats($partner);
        $withdrawals = $portal->withdrawals($partner);
        $pendingAmount = collect($withdrawals)
            ->where('status', 'pending')
            ->sum('amount');

        return $this->withLocalizedTitle(view('livewire.partner.earnings', [
            'stats' => $stats,
            'earningsHistory' => [],
            'withdrawals' => $withdrawals,
            'pendingAmount' => $pendingAmount,
            'lifetimeAmount' => $stats['earnings'],
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.earnings')),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.nav.earnings');
    }
}
