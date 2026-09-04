@props([
    'align' => 'end',
])

<div
    x-data="{ open: false }"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    <div @click="open = !open" role="button" tabindex="0" @keydown.enter="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        @click.outside="open = false"
        x-cloak
        @class([
            'absolute z-50 mt-2 min-w-[12rem] rounded-xl border border-surface-border bg-surface-card py-1 shadow-lg dark:border-brand-border dark:bg-brand-card',
            'start-0' => $align === 'start',
            'end-0' => $align === 'end',
        ])
    >
        {{ $slot }}
    </div>
</div>
