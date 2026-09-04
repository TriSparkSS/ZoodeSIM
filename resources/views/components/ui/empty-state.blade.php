@props([
    'title' => null,
    'description' => null,
    'icon' => '📭',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-surface-border bg-surface-card-alt/60 px-6 py-16 text-center dark:border-brand-border dark:bg-brand-card/50']) }}>
    <div class="mb-4 text-5xl">{{ $icon }}</div>
    <h3 class="mb-2 text-lg font-bold text-surface-text dark:text-brand-text">{{ $title ?? __('ui.empty_state_title') }}</h3>
    <p class="max-w-sm text-sm text-surface-muted dark:text-brand-muted">{{ $description ?? __('ui.empty_state_description') }}</p>
    @if(isset($action))
        <div class="mt-6">{{ $action }}</div>
    @endif
</div>
