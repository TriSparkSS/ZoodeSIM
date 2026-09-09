<div class="mb-7 light-card flex flex-wrap items-center justify-between gap-4 p-5">
    <div>
        <div class="mb-2.5 text-xs uppercase tracking-wide text-surface-muted dark:text-brand-muted">
            {{ __('admin.statistics.resellportal_balance') }}
        </div>
        @if ($providerBalance->available)
            <div class="text-3xl font-extrabold leading-none text-brand-cyan">{{ $providerBalance->formatted() }}</div>
            @if ($providerBalance->fromCache)
                <div class="mt-2 text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.statistics.balance_cached') }}</div>
            @endif
        @else
            <div class="text-sm text-surface-muted dark:text-brand-muted">{{ __('admin.statistics.balance_unavailable') }}</div>
        @endif
    </div>
    <x-ui.button
        variant="secondary"
        size="sm"
        wire:click="refreshBalance"
        wire:loading.attr="disabled"
    >
        {{ __('admin.statistics.balance_refresh') }}
    </x-ui.button>
</div>
