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
class Dashboard extends Component
{
    use ResolvesAuthenticatedPartner;
    use WithPartnerNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public function copyPromoCode(PartnerPortalDataService $portal): void
    {
        $code = $portal->stats($this->partner())['promo_code'];

        if ($code === '—') {
            return;
        }

        $this->dispatch('copy-to-clipboard', text: $code);
        $this->toast(__('partner.dashboard.code_copied', ['code' => $code]));
    }

    public function requestWithdrawal(PartnerPortalDataService $portal): void
    {
        $amount = $portal->stats($this->partner())['available_withdrawal'];
        $this->toast(__('partner.dashboard.withdraw_requested', ['amount' => '$'.number_format($amount, 2)]));
    }

    public function render(PartnerPortalDataService $portal)
    {
        $partner = $this->partner();

        return $this->withLocalizedTitle(view('livewire.partner.dashboard', [
            'partnerName' => $partner->name,
            'stats' => $portal->stats($partner),
            'registrations' => $portal->registrations($partner),
            'promoCodes' => $portal->promoCodes($partner),
            'chartData' => [],
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.overview')),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.dashboard.title');
    }
}
