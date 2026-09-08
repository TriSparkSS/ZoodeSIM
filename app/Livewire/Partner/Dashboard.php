<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\RequestsPartnerWithdrawal;
use App\Livewire\Concerns\ResolvesAuthenticatedPartner;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithPartnerNavigation;
use App\Livewire\Concerns\WithToast;
use App\Models\Withdrawal;
use App\Services\Partner\Contracts\WithdrawalServiceInterface;
use App\Services\Partner\PartnerPortalDataService;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.partner')]
class Dashboard extends Component
{
    use RequestsPartnerWithdrawal;
    use ResolvesAuthenticatedPartner;
    use WithLocalizedTitle;
    use WithPartnerNavigation;
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

    public function render(PartnerPortalDataService $portal, WithdrawalServiceInterface $withdrawals)
    {
        $partner = $this->partner()->fresh();
        $minimum = Money::fromDecimal($withdrawals->minimumAmount(), (string) config('pricing.currency', 'USD'));
        $chartData = $portal->dailyRegistrations($partner);

        return $this->withLocalizedTitle(view('livewire.partner.dashboard', [
            'partnerName' => $partner->name,
            'stats' => $portal->stats($partner),
            'registrations' => $portal->registrations($partner),
            'promoCodes' => $portal->promoCodes($partner),
            'chartData' => $chartData,
            'weekTotal' => collect($chartData)->sum('count'),
            'methods' => Withdrawal::methods(),
            'minWithdrawal' => $minimum->format(),
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.overview')),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.dashboard.title');
    }
}
