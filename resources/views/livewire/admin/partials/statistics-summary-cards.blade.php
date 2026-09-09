<div class="mb-7 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-ui.stat-card
        :label="__('admin.statistics.total_partners')"
        :value="(string) $stats['total_partners']"
        color="cyan"
    />
    <x-ui.stat-card
        :label="__('admin.statistics.total_registrations')"
        :value="number_format($stats['total_registrations'])"
        color="green"
    />
    <x-ui.stat-card
        :label="__('admin.statistics.total_payouts')"
        :value="'$'.number_format($stats['total_payouts'], 2)"
        color="purple"
    />
    <x-ui.stat-card
        :label="__('admin.statistics.active_codes')"
        :value="(string) $stats['active_codes']"
        color="yellow"
    />
</div>
