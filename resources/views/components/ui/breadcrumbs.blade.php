@props(['items' => []])

<nav {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2 text-sm text-surface-muted dark:text-brand-muted']) }} aria-label="Breadcrumb">
    @foreach($items as $index => $item)
        @if($index > 0)
            <span class="text-surface-muted/50 dark:text-brand-muted/50">/</span>
        @endif

        @if(isset($item['href']) && ! ($item['active'] ?? false))
            <a href="{{ $item['href'] }}" class="transition-colors hover:text-brand-cyan">{{ $item['label'] }}</a>
        @else
            <span @class([
                'font-medium text-surface-text dark:text-brand-text' => $item['active'] ?? ($index === count($items) - 1),
            ])>{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
