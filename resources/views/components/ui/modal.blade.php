@props([
    'title' => null,
    'maxWidth' => 'max-w-lg',
])

<div
    x-data="{ show: @entangle($attributes->wire('model')).live }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    <div @click="show = false" class="absolute inset-0 bg-black/50 backdrop-blur-sm dark:bg-black/70"></div>

    <div class="relative w-full {{ $maxWidth }} rounded-2xl border border-surface-border bg-surface-card p-6 text-surface-text shadow-2xl dark:border-brand-border dark:bg-brand-card dark:text-brand-text md:p-8">
        @if($title)
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-bold text-surface-text dark:text-brand-text">{{ $title }}</h3>
                <button type="button" @click="show = false" class="text-surface-muted transition-colors hover:text-surface-text dark:text-brand-muted dark:hover:text-brand-text">✕</button>
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
