<div>
    <x-ui.page-header
        :title="__('admin.dashboard.title')"
        :subtitle="__('admin.dashboard.subtitle')"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <a
                href="{{ route('admin.statistics') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-surface-border bg-surface-card px-3 py-1.5 text-xs font-semibold text-surface-text transition-all hover:border-brand-cyan/40 dark:border-brand-border dark:bg-brand-card-alt dark:text-brand-text"
            >
                {{ __('admin.dashboard.view_statistics') }}
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @include('livewire.admin.partials.resellportal-balance')
    @include('livewire.admin.partials.statistics-summary-cards')
    @include('livewire.admin.partials.recent-applications')
</div>
