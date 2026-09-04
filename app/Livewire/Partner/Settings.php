<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\ResolvesAuthenticatedPartner;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithPartnerNavigation;
use App\Livewire\Concerns\WithToast;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.partner')]
class Settings extends Component
{
    use ResolvesAuthenticatedPartner;
    use WithPartnerNavigation;
    use WithLocalizedTitle;
    use WithToast;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $payoutMethod = 'paypal';

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
        $this->payoutDetails = $partner->email;
    }

    public function save(): void
    {
        $partner = $this->partner();
        Gate::forUser($partner)->authorize('update', $partner);

        $validated = $this->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:partners,email,'.$partner->id.',id'],
            'payoutMethod' => ['required', 'string', 'max:50'],
            'payoutDetails' => ['nullable', 'string', 'max:255'],
        ]);

        $partner->update([
            'name' => trim($validated['firstName'].' '.($validated['lastName'] ?? '')),
            'email' => $validated['email'],
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
