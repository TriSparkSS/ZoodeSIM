<?php

namespace App\Livewire\Concerns;

trait WithAdminNavigation
{
    protected function adminNavItems(): array
    {
        return [
            ['label' => __('admin.nav.dashboard'), 'href' => route('admin.dashboard'), 'icon' => '🏠', 'active' => request()->routeIs('admin.dashboard')],
            ['label' => __('admin.nav.partners'), 'href' => route('admin.partners'), 'icon' => '👥', 'active' => request()->routeIs('admin.partners')],
            ['label' => __('admin.nav.users'), 'href' => route('admin.users'), 'icon' => '👤', 'active' => request()->routeIs('admin.users')],
            ['label' => __('admin.nav.orders'), 'href' => route('admin.orders'), 'icon' => '📦', 'active' => request()->routeIs('admin.orders')],
            ['label' => __('admin.nav.promo_codes'), 'href' => route('admin.promo-codes'), 'icon' => '🎟️', 'active' => request()->routeIs('admin.promo-codes')],
            ['label' => __('admin.nav.promo_audit'), 'href' => route('admin.promo-audit'), 'icon' => '🧾', 'active' => request()->routeIs('admin.promo-audit')],
            ['label' => __('admin.nav.pricing_slabs'), 'href' => route('admin.pricing-slabs'), 'icon' => '💲', 'active' => request()->routeIs('admin.pricing-slabs')],
            ['label' => __('admin.nav.countries'), 'href' => route('admin.countries'), 'icon' => '🌍', 'active' => request()->routeIs('admin.countries')],
            ['label' => __('admin.nav.banners'), 'href' => route('admin.banners'), 'icon' => '🖼️', 'active' => request()->routeIs('admin.banners')],
            ['label' => __('admin.nav.payouts'), 'href' => route('admin.payouts'), 'icon' => '💰', 'active' => request()->routeIs('admin.payouts')],
            ['label' => __('admin.nav.transactions'), 'href' => route('admin.transactions'), 'icon' => '📒', 'active' => request()->routeIs('admin.transactions')],
            ['label' => __('admin.nav.statistics'), 'href' => route('admin.statistics'), 'icon' => '📊', 'active' => request()->routeIs('admin.statistics')],
            ['label' => __('admin.nav.api_logs'), 'href' => route('admin.api-logs'), 'icon' => '📡', 'active' => request()->routeIs('admin.api-logs*')],
            ['label' => __('admin.nav.settings'), 'href' => route('admin.settings'), 'icon' => '⚙️', 'active' => request()->routeIs('admin.settings')],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function adminBreadcrumbs(string $current): array
    {
        return [
            ['label' => __('ui.admin_panel'), 'href' => route('admin.dashboard')],
            ['label' => $current, 'active' => true],
        ];
    }
}
