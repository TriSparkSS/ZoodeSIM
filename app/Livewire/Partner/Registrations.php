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
class Registrations extends Component
{
    use ResolvesAuthenticatedPartner;
    use WithLocalizedTitle;
    use WithPartnerNavigation;
    use WithToast;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function render(PartnerPortalDataService $portal)
    {
        $partner = $this->partner();

        return $this->withLocalizedTitle(view('livewire.partner.registrations', [
            'registrations' => $portal->registrations(
                $partner,
                $this->search,
                $this->dateFrom !== '' ? $this->dateFrom : null,
                $this->dateTo !== '' ? $this->dateTo : null,
            ),
            'totalCount' => (int) $portal->stats($partner)['registrations'],
            'thisMonthCount' => $portal->registrationsThisMonth($partner),
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.registrations')),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.nav.registrations');
    }
}
