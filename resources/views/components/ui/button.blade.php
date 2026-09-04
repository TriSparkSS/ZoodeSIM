@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'gradient-bg text-white hover:opacity-90 shadow-sm',
        'secondary' => 'border border-surface-border bg-surface-card text-surface-text hover:border-brand-cyan/40 hover:bg-surface-card-alt dark:border-brand-border dark:bg-brand-card-alt dark:text-brand-text dark:hover:bg-brand-card',
        'success' => 'border border-brand-green/30 bg-brand-green/10 text-brand-green hover:bg-brand-green/20',
        'danger' => 'border border-brand-red/30 bg-brand-red/10 text-brand-red hover:bg-brand-red/20',
        'ghost' => 'text-surface-muted hover:bg-brand-cyan/5 hover:text-surface-text dark:text-brand-muted dark:hover:text-brand-text',
        'outline' => 'border border-brand-cyan/30 bg-brand-cyan/10 text-brand-cyan hover:bg-brand-cyan/20',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs rounded-lg',
        'md' => 'px-4 py-2.5 text-sm rounded-xl',
        'lg' => 'px-6 py-3 text-base rounded-xl',
    ];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-50 ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md'])]) }}
>
    {{ $slot }}
</button>
