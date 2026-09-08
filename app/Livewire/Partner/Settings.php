<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\ResolvesAuthenticatedPartner;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithPartnerNavigation;
use App\Livewire\Concerns\WithToast;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.partner')]
class Settings extends Component
{
    use ResolvesAuthenticatedPartner;
    use WithLocalizedTitle;
    use WithPartnerNavigation;
    use WithToast;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $payoutMethod = Withdrawal::METHOD_CARD;

    public string $payoutDetails = '';

    public bool $emailNotifications = true;

    public bool $telegramNotifications = false;

    public function mount(): void
    {
        $partner = $this->partner();
        $parts = preg_split('/\s+/', trim($partner->name), 2) ?: ['', ''];

        $this->firstName = $parts[0] ?? '';
        $this->lastName = $parts[1] ?? '';
        $this->email = $partner->email;
        $this->payoutMethod = $partner->payout_method ?: Withdrawal::METHOD_CARD;
        $this->payoutDetails = $partner->payout_details ?: $partner->email;
        $this->emailNotifications = (bool) $partner->email_notifications;
        $this->telegramNotifications = (bool) $partner->telegram_notifications;
    }

    public function save(): void
    {
        $partner = $this->partner();
        Gate::forUser($partner)->authorize('update', $partner);

        $validated = $this->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:partners,email,'.$partner->id.',id'],
            'payoutMethod' => ['required', 'in:'.implode(',', Withdrawal::methods())],
            'payoutDetails' => ['required', 'string', 'max:255'],
            'emailNotifications' => ['boolean'],
            'telegramNotifications' => ['boolean'],
        ]);

        $partner->update([
            'name' => trim($validated['firstName'].' '.($validated['lastName'] ?? '')),
            'email' => $validated['email'],
            'payout_method' => $validated['payoutMethod'],
            'payout_details' => $validated['payoutDetails'],
            'email_notifications' => (bool) $this->emailNotifications,
            'telegram_notifications' => (bool) $this->telegramNotifications,
        ]);

        $this->toast(__('partner.settings.saved'));
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.partner.settings', [
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.settings')),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.nav.settings');
    }
}
