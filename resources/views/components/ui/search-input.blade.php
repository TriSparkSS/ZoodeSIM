@props([
    'placeholder' => null,
])

<div class="relative">
    <span class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-brand-muted">🔍</span>
    <input
        type="search"
        placeholder="{{ $placeholder ?? __('ui.search_placeholder') }}"
        {{ $attributes->merge(['class' => 'w-full rounded-xl border border-surface-border bg-surface-card-alt py-2.5 ps-10 pe-4 text-sm outline-none transition-colors focus:border-brand-cyan dark:border-brand-border dark:bg-brand-card-alt dark:text-brand-text']) }}
    />
</div>
