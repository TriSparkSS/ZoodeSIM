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
class PromoCodes extends Component
{
    use ResolvesAuthenticatedPartner;
    use WithPartnerNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public bool $showCreateModal = false;

    public string $newCode = '';

    public string $newBonus = '200';

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function render(PartnerPortalDataService $portal)
    {
        return $this->withLocalizedTitle(view('livewire.partner.promo-codes', [
            'promoCodes' => $portal->promoCodes($this->partner()),
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.promo_codes')),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.nav.promo_codes');
    }
}
