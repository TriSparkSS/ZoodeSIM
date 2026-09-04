@props([
    'type' => 'default',
])

@php
    $types = [
        'active' => 'bg-brand-green/12 text-brand-green',
        'pending' => 'bg-brand-yellow/12 text-brand-yellow',
        'approved' => 'bg-brand-green/12 text-brand-green',
        'rejected' => 'bg-brand-red/12 text-brand-red',
        'completed' => 'bg-brand-cyan/12 text-brand-cyan',
        'expired' => 'bg-brand-yellow/12 text-brand-yellow',
        'exhausted' => 'bg-brand-red/12 text-brand-red',
        'inactive' => 'bg-surface-muted/20 text-surface-muted dark:bg-brand-muted/20 dark:text-brand-muted',
        'default' => 'bg-brand-cyan/12 text-brand-cyan',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-bold whitespace-nowrap ' . ($types[$type] ?? $types['default'])]) }}>
    {{ $slot }}
</span>
