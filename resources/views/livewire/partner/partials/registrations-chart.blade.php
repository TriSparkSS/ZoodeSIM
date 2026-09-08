<x-ui.card :title="__('partner.dashboard.registrations_chart')">
    <div class="mb-3 flex h-20 items-end gap-1.5">
        @forelse($chartData as $bar)
            <div class="flex flex-1 flex-col items-center gap-1">
                <div
                    class="w-full min-h-0.5 rounded-t bg-gradient-to-t from-brand-purple to-brand-cyan opacity-70 transition-opacity hover:opacity-100"
                    style="height: {{ $bar['value'] }}%"
                    title="{{ $bar['count'] }}"
                ></div>
                <span class="text-[10px] text-brand-muted">{{ __('ui.days.'.$bar['label']) }}</span>
            </div>
        @empty
            <p class="w-full text-sm text-surface-muted dark:text-brand-muted">{{ __('ui.no_data') }}</p>
        @endforelse
    </div>
    <p class="text-xs text-brand-muted">
        {{ __('partner.dashboard.week_total', ['count' => $weekTotal]) }}
    </p>
</x-ui.card>
