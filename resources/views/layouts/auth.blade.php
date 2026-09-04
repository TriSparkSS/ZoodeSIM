<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('locales.supported.'.app()->getLocale().'.rtl') ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ui.brand') }} — {{ $portalTitle ?? __('auth.login') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="min-h-full bg-surface text-surface-text antialiased dark:bg-brand-bg dark:text-brand-text">
    <div class="auth-shell flex min-h-screen flex-col">
        <header class="flex items-center justify-between border-b border-brand-border/80 bg-brand-card/40 px-6 py-3 backdrop-blur-sm md:px-8 dark:bg-brand-card/40">
            <a href="{{ route('apply') }}" class="text-[22px] font-black tracking-tight text-surface-text dark:text-brand-text">
                Zoode<span class="gradient-text">SIM</span>
            </a>
            <div class="flex items-center gap-2">
                <x-ui.locale-switcher />
                <x-ui.theme-toggle />
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-6 py-10">
            {{ $slot }}
        </main>
    </div>
    <x-ui.toast />
    @livewireScripts
</body>
</html>
