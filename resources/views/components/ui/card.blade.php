@props([
    'title' => null,
    'action' => null,
    'actionHref' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'light-card overflow-hidden']) }}>
    @if($title)
        <div @class(['flex items-center justify-between', 'border-b border-surface-border px-6 py-5 dark:border-brand-border' => $padding, 'mb-0' => ! $padding])>
            <h3 class="text-base font-bold text-surface-text dark:text-brand-text">{{ $title }}</h3>
            @if($action)
                <a href="{{ $actionHref ?? '#' }}" class="text-xs text-surface-muted transition-colors hover:text-brand-cyan dark:text-brand-muted">{{ $action }}</a>
            @endif
        </div>
    @endif
    <div @class(['p-6' => $padding && $title, 'p-5' => $padding && ! $title])>
        {{ $slot }}
    </div>
</div>
