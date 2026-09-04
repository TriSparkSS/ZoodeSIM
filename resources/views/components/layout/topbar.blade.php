@props(['variant' => 'partner'])

@php
    $isAdmin = $variant === 'admin';
    $settingsUrl = $isAdmin ? route('admin.settings') : route('partner.settings');
    $profileUrl = $isAdmin ? route('admin.profile') : route('partner.settings');
    $user = $isAdmin ? auth('admin')->user() : auth('partner')->user();
    $initial = $user?->name ? mb_strtoupper(mb_substr($user->name, 0, 1)) : '?';
@endphp

<header {{ $attributes->merge(['class' => 'sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-surface-border bg-surface/80 px-5 py-3 backdrop-blur-md dark:border-brand-border dark:bg-brand-bg/80 md:px-8']) }}>
    <div class="flex items-center gap-3 lg:hidden">
        <div class="text-lg font-black text-surface-text dark:text-brand-text">
            Zoode<span class="gradient-text">SIM</span>
        </div>
    </div>

    <div class="hidden flex-1 lg:block">
        {{ $slot ?? '' }}
    </div>

    <div class="flex items-center gap-2 md:gap-3">
        <x-ui.locale-switcher />
        <x-ui.theme-toggle />

        <x-ui.dropdown align="end">
            <x-slot:trigger>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full gradient-bg text-sm font-bold text-white"
                    aria-label="{{ __('ui.profile') }}"
                >
                    {{ $initial }}
                </button>
            </x-slot:trigger>

            @if($user)
                <div class="border-b border-surface-border px-4 py-2 dark:border-brand-border">
                    <div class="text-sm font-semibold text-surface-text dark:text-brand-text">{{ $user->name }}</div>
                    <div class="text-xs text-brand-muted">{{ $user->email }}</div>
                </div>
            @endif

            <a href="{{ $profileUrl }}" class="block px-4 py-2 text-sm transition-colors hover:bg-brand-cyan/5">{{ __('ui.profile') }}</a>
            <a href="{{ $settingsUrl }}" class="block px-4 py-2 text-sm transition-colors hover:bg-brand-cyan/5">{{ __('ui.settings') }}</a>
            <hr class="my-1 border-surface-border dark:border-brand-border">
            @if($isAdmin)
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-start text-sm text-brand-red transition-colors hover:bg-brand-red/5">
                        {{ __('ui.logout') }}
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('partner.logout') }}">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2 text-start text-sm text-brand-red transition-colors hover:bg-brand-red/5">
                        {{ __('ui.logout') }}
                    </button>
                </form>
            @endif
        </x-ui.dropdown>
    </div>
</header>
