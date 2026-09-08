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
class Earnings extends Component
{
    use RequestsPartnerWithdrawal;
    use ResolvesAuthenticatedPartner;
    use WithLocalizedTitle;
    use WithPartnerNavigation;
    use WithToast;

    public string $filter = 'all';

    public function render(PartnerPortalDataService $portal, WithdrawalServiceInterface $withdrawals)
    {
        $partner = $this->partner()->fresh();
        $stats = $portal->stats($partner);
        $withdrawalRows = $portal->withdrawals($partner);
        $pendingAmount = collect($withdrawalRows)
            ->whereIn('status', [Withdrawal::STATUS_PENDING, Withdrawal::STATUS_PROCESSING])
            ->sum('amount');
        $minimum = Money::fromDecimal($withdrawals->minimumAmount(), (string) config('pricing.currency', 'USD'));

        return $this->withLocalizedTitle(view('livewire.partner.earnings', [
            'stats' => $stats,
            'earningsHistory' => $portal->earningsHistory($partner, $this->filter),
            'walletTransactions' => $portal->walletTransactions($partner),
            'withdrawals' => $withdrawalRows,
            'pendingAmount' => $pendingAmount,
            'lifetimeAmount' => $stats['earnings'],
            'minWithdrawal' => $minimum->format(),
            'methods' => Withdrawal::methods(),
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.earnings')),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.nav.earnings');
    }
}
