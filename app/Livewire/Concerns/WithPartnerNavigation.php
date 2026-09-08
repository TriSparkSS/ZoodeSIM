<?php

namespace App\Livewire\Concerns;

trait WithPartnerNavigation
{
    protected function partnerNavItems(): array
    {
        $unread = 0;

        if (method_exists($this, 'partner')) {
            $unread = $this->partner()->unreadNotifications()->count();
        }

        return [
            ['label' => __('partner.nav.overview'), 'href' => route('partner.dashboard'), 'icon' => '📊', 'active' => request()->routeIs('partner.dashboard')],
            ['label' => __('partner.nav.registrations'), 'href' => route('partner.registrations'), 'icon' => '👥', 'active' => request()->routeIs('partner.registrations')],
            ['label' => __('partner.nav.earnings'), 'href' => route('partner.earnings'), 'icon' => '💰', 'active' => request()->routeIs('partner.earnings')],
            ['label' => __('partner.nav.promo_codes'), 'href' => route('partner.promo-codes'), 'icon' => '🎟️', 'active' => request()->routeIs('partner.promo-codes')],
            ['label' => __('partner.nav.statistics'), 'href' => route('partner.statistics'), 'icon' => '📈', 'active' => request()->routeIs('partner.statistics')],
            ['label' => __('partner.nav.notifications'), 'href' => route('partner.notifications'), 'icon' => '🔔', 'active' => request()->routeIs('partner.notifications'), 'badge' => $unread],
            ['label' => __('partner.nav.settings'), 'href' => route('partner.settings'), 'icon' => '⚙️', 'active' => request()->routeIs('partner.settings')],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function partnerBreadcrumbs(string $current): array
    {
        return [
            ['label' => __('ui.home'), 'href' => route('partner.dashboard')],
            ['label' => $current, 'active' => true],
        ];
    }
}
