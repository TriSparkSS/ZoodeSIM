<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('locales.supported.'.app()->getLocale().'.rtl') ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ui.brand') }} — {{ __('ui.admin_panel') }}</title>
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
    <div class="flex min-h-screen flex-col">
        <div class="flex min-h-screen flex-1">
            <x-layout.sidebar :items="$navItems ?? []" :portal="__('ui.admin_panel')" :home-url="route('admin.dashboard')" />
            <div class="flex min-h-full flex-1 flex-col lg:ms-[230px]">
                <x-layout.topbar variant="admin" />
                <main class="flex-1 overflow-y-auto p-5 md:p-7">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
    <x-ui.toast />
    @livewireScripts
</body>
</html>
