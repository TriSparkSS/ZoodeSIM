<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('locales.supported.'.app()->getLocale().'.rtl') ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ui.brand') }} — {{ $portalTitle ?? __('ui.partner_portal') }}</title>
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
    <div class="flex min-h-screen">
        <x-layout.sidebar :items="$navItems ?? []" :portal="$portalTitle ?? __('ui.partner_portal')" />

        <div class="flex min-h-screen flex-1 flex-col lg:ms-[230px]">
            <x-layout.topbar />
            <main class="flex-1 p-5 md:p-8">
                <x-partner.banners />
                {{ $slot }}
            </main>
        </div>
    </div>
    <x-ui.toast />
    @livewireScripts
</body>
</html>
