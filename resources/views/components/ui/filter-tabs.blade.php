@props([
    'tabs' => [],
    'active' => '',
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1.5']) }}>
    @foreach($tabs as $key => $label)
        <button
            type="button"
            wire:click="$set('filter', '{{ $key }}')"
            @class([
                'rounded-lg border px-3.5 py-1.5 text-xs font-semibold transition-all duration-200',
                'border-brand-cyan bg-brand-cyan/10 text-brand-cyan' => $active === $key,
                'border-brand-border text-brand-muted hover:text-brand-text' => $active !== $key,
            ])
        >
            {{ $label }}
        </button>
    @endforeach
</div>
