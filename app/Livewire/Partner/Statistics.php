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
class Statistics extends Component
{
    use ResolvesAuthenticatedPartner;
    use WithPartnerNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public function render(PartnerPortalDataService $portal)
    {
        $partner = $this->partner();
        $stats = $portal->stats($partner);
        $promoCodes = $portal->promoCodes($partner);
        $topCode = collect($promoCodes)->sortByDesc('uses')->first();

        return $this->withLocalizedTitle(view('livewire.partner.statistics', [
            'stats' => $stats,
            'chartData' => [],
            'monthlyTrend' => [],
            'topCode' => $topCode,
            'avgEarnings' => $stats['registrations'] > 0
                ? round($stats['earnings'] / $stats['registrations'], 2)
                : 0,
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.statistics')),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.nav.statistics');
    }
}
