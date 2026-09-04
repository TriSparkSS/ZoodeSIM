@props([
    'label' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'up',
    'color' => 'cyan',
])

@php
    $valueColors = [
        'cyan' => 'text-brand-cyan',
        'green' => 'text-brand-green',
        'purple' => 'text-brand-purple',
        'white' => 'text-surface-text dark:text-white',
        'yellow' => 'text-brand-yellow',
        'red' => 'text-brand-red',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'light-card p-5 transition-colors hover:border-brand-cyan/30']) }}>
    <div class="mb-2.5 text-xs uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ $label }}</div>
    <div class="mb-2 text-3xl font-extrabold leading-none {{ $valueColors[$color] ?? $valueColors['cyan'] }}">{{ $value }}</div>
    @if($change)
        <div @class([
            'flex items-center gap-1 text-xs',
            'text-brand-green' => $changeType === 'up',
            'text-brand-red' => $changeType === 'down',
            'text-surface-muted dark:text-brand-muted' => $changeType === 'neutral',
        ])>
            {{ $change }}
        </div>
    @endif
</div>
