@props([
    'name' => null,
    'error' => null,
])

<select
    @if($name) name="{{ $name }}" id="{{ $name }}" @endif
    {{ $attributes->merge([
        'class' => 'w-full cursor-pointer appearance-none rounded-xl border border-surface-border bg-surface-card-alt px-4 py-3 text-sm text-surface-text outline-none transition-colors focus:border-brand-cyan dark:border-brand-border dark:bg-brand-card-alt dark:text-brand-text' . ($error ? ' border-brand-red' : ''),
    ]) }}
>
    {{ $slot }}
</select>
