<header class="border-b border-brand-border bg-brand-card px-6 md:px-8">
    <div class="flex items-center justify-between py-3">
        <a href="{{ route('apply') }}" class="text-lg font-black text-brand-text">
            Zoode<span class="gradient-text">SIM</span>
        </a>
        <div class="flex items-center gap-3">
            <x-ui.locale-switcher />
            <x-ui.theme-toggle />
            <a href="{{ route('login') }}" class="hidden text-sm text-brand-muted hover:text-brand-cyan sm:inline">{{ __('ui.partner_login') }}</a>
        </div>
    </div>
</header>
