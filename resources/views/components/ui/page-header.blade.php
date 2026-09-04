@props([
    'title' => '',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'mb-8']) }}>
    @if(count($breadcrumbs) > 0)
        <x-ui.breadcrumbs :items="$breadcrumbs" class="mb-3" />
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-surface-text dark:text-brand-text">{{ $title }}</h1>
            @if($subtitle)
                <p class="mt-1 text-sm text-surface-muted dark:text-brand-muted">{{ $subtitle }}</p>
            @endif
        </div>
        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
        @endif
    </div>
</div>
