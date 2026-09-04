@props([
    'current' => 1,
    'total' => 1,
    'perPage' => 10,
    'itemCount' => 0,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-between gap-4 border-t border-brand-border pt-5 sm:flex-row']) }}>
    <span class="text-xs text-brand-muted">
        {{ __('ui.showing_results', ['count' => $itemCount, 'total' => $total * $perPage]) }}
    </span>

    <div class="flex items-center gap-2">
        <x-ui.button variant="secondary" size="sm" :disabled="$current <= 1">
            ← {{ __('ui.previous') }}
        </x-ui.button>

        @for($page = 1; $page <= $total; $page++)
            <span @class([
                'rounded-lg border px-3 py-1.5 text-xs font-semibold',
                'border-brand-cyan bg-brand-cyan/10 text-brand-cyan' => $page === $current,
                'border-brand-border text-brand-muted' => $page !== $current,
            ])>
                {{ $page }}
            </span>
        @endfor

        <x-ui.button variant="secondary" size="sm" :disabled="$current >= $total">
            {{ __('ui.next') }} →
        </x-ui.button>
    </div>
</div>
