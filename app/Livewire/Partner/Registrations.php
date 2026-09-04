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
    use WithPartnerNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public string $search = '';

    public function render(PartnerPortalDataService $portal)
    {
        $partner = $this->partner();
        $allRegistrations = $portal->registrations($partner);
        $stats = $portal->stats($partner);

        $registrations = collect($allRegistrations)
            ->when($this->search !== '', function ($collection) {
                $query = strtolower($this->search);

                return $collection->filter(function (array $registration) use ($query) {
                    return str_contains(strtolower($registration['name']), $query)
                        || str_contains(strtolower($registration['code']), $query);
                });
            })
            ->values()
            ->all();

        return $this->withLocalizedTitle(view('livewire.partner.registrations', [
            'registrations' => $registrations,
            'stats' => $stats,
            'totalCount' => count($allRegistrations),
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.registrations')),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.nav.registrations');
    }
}
