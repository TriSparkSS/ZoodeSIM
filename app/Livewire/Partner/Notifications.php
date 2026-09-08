<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\ResolvesAuthenticatedPartner;
use App\Livewire\Concerns\WithLocalizedTitle;
use App\Livewire\Concerns\WithPartnerNavigation;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.partner')]
class Notifications extends Component
{
    use ResolvesAuthenticatedPartner;
    use WithLocalizedTitle;
    use WithPartnerNavigation;

    public function markRead(string $notificationId): void
    {
        $item = $this->partner()->notifications()->whereKey($notificationId)->first();
        $item?->markAsRead();
    }

    public function markAllRead(): void
    {
        $this->partner()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $partner = $this->partner();

        return $this->withLocalizedTitle(view('livewire.partner.notifications', [
            'breadcrumbs' => $this->partnerBreadcrumbs(__('partner.nav.notifications')),
            'notifications' => $partner->notifications()->latest()->limit(50)->get(),
            'unreadCount' => $partner->unreadNotifications()->count(),
        ])->layout('layouts.partner', [
            'navItems' => $this->partnerNavItems(),
            'portalTitle' => __('ui.partner_portal'),
        ]), 'partner.nav.notifications');
    }
}
