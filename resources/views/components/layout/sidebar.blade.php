@props([
    'items' => [],
    'portal' => '',
    'homeUrl' => null,
])

@php
    $homeUrl = $homeUrl ?? route('partner.dashboard');
@endphp

<aside {{ $attributes->merge(['class' => 'fixed inset-y-0 start-0 z-30 hidden w-[230px] flex-col border-e border-surface-border bg-surface-card dark:border-brand-border dark:bg-brand-card lg:flex']) }}>
    <div class="border-b border-surface-border px-6 pb-7 pt-6 dark:border-brand-border">
        <a href="{{ $homeUrl }}" class="block">
            <div class="text-[22px] font-black tracking-tight text-surface-text dark:text-brand-text">
                Zoode<span class="gradient-text">SIM</span>
            </div>
            <div class="mt-0.5 text-[10px] uppercase tracking-[0.2em] text-surface-muted dark:text-brand-muted">{{ $portal }}</div>
        </a>
    </div>

    <nav class="flex-1 space-y-0.5 py-5">
        @foreach($items as $item)
            <a
                href="{{ $item['href'] }}"
                @class([
                    'flex items-center gap-3 border-s-[3px] px-6 py-3 text-sm transition-all duration-200',
                    'border-brand-cyan bg-brand-cyan/8 text-brand-cyan' => $item['active'] ?? false,
                    'border-transparent text-surface-muted hover:bg-brand-cyan/5 hover:text-surface-text dark:text-brand-muted dark:hover:text-brand-text' => ! ($item['active'] ?? false),
                ])
            >
                <span class="w-5 text-center text-lg">{{ $item['icon'] ?? '•' }}</span>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if(($item['badge'] ?? 0) > 0)
                    <span class="rounded-full bg-brand-cyan px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="border-t border-surface-border p-5 dark:border-brand-border">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-full gradient-bg text-sm font-bold text-white">
                S
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[13px] font-semibold text-surface-text dark:text-brand-text">Sardor</div>
                <div class="truncate text-[11px] text-surface-muted dark:text-brand-muted">{{ __('partner.user.role', ['level' => 2]) }}</div>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile sidebar overlay -->
<div x-data="{ open: false }" class="lg:hidden">
    <button
        type="button"
        @click="open = true"
        class="fixed bottom-5 start-5 z-40 flex h-12 w-12 items-center justify-center rounded-full gradient-bg text-white shadow-lg"
        aria-label="{{ __('ui.menu') }}"
    >
        ☰
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50">
        <div @click="open = false" class="absolute inset-0 bg-black/60"></div>
        <aside class="absolute inset-y-0 start-0 flex w-[230px] flex-col border-e border-surface-border bg-surface-card dark:border-brand-border dark:bg-brand-card">
            <div class="flex items-center justify-between border-b border-surface-border px-6 py-5 dark:border-brand-border">
                <div>
                    <div class="text-lg font-black text-surface-text dark:text-brand-text">Zoode<span class="gradient-text">SIM</span></div>
                    <div class="text-[10px] uppercase tracking-widest text-surface-muted dark:text-brand-muted">{{ $portal }}</div>
                </div>
                <button @click="open = false" class="text-surface-muted dark:text-brand-muted" aria-label="{{ __('ui.close') }}">✕</button>
            </div>
            <nav class="flex-1 py-4">
                @foreach($items as $item)
                    <a
                        href="{{ $item['href'] }}"
                        @class([
                            'flex items-center gap-3 px-6 py-3 text-sm',
                            'text-brand-cyan' => $item['active'] ?? false,
                            'text-surface-muted hover:text-surface-text dark:text-brand-muted dark:hover:text-brand-text' => ! ($item['active'] ?? false),
                        ])
                    >
                        <span>{{ $item['icon'] ?? '•' }}</span>
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if(($item['badge'] ?? 0) > 0)
                            <span class="rounded-full bg-brand-cyan px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </aside>
    </div>
</div>
