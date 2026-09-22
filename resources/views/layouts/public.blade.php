<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('locales.supported.'.app()->getLocale().'.rtl') ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ui.brand') }}</title>
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
    <x-layout.public-header />
    <main>{{ $slot }}</main>
    <footer class="border-t border-brand-border bg-brand-card px-6 py-5 md:px-8">
        <div class="mx-auto flex max-w-5xl flex-col items-center gap-3 text-center sm:flex-row sm:items-center sm:justify-between sm:text-start">
            <div>
                <div class="text-sm font-black text-brand-text">Zoode<span class="gradient-text">SIM</span></div>
                <p class="mt-0.5 max-w-xs text-xs leading-relaxed text-brand-muted">{{ __('ui.legal.footer_tagline') }}</p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-brand-muted">
                <a href="{{ route('legal.privacy') }}" class="hover:text-brand-cyan">{{ __('ui.privacy') }}</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-brand-cyan">{{ __('ui.terms') }}</a>
                <a href="{{ route('legal.delete-account') }}" class="hover:text-brand-cyan">{{ __('ui.delete_account') }}</a>
                <a href="{{ route('apply') }}" class="hover:text-brand-cyan">{{ __('ui.apply_now') }}</a>
            </div>
        </div>
    </footer>
    <x-ui.toast />
    @livewireScripts
</body>
</html>
