<header class="border-b border-brand-border bg-brand-card px-6 md:px-8">
    <div class="flex items-center justify-between gap-4 py-3">
        <a href="{{ route('apply') }}" class="shrink-0 text-lg font-black text-brand-text">
            Zoode<span class="gradient-text">SIM</span>
        </a>
        <nav class="hidden items-center gap-5 text-sm text-brand-muted md:flex">
            <a
                href="{{ route('legal.privacy') }}"
                @class(['hover:text-brand-cyan', 'text-brand-cyan' => request()->routeIs('legal.privacy')])
            >{{ __('ui.privacy') }}</a>
            <a
                href="{{ route('legal.terms') }}"
                @class(['hover:text-brand-cyan', 'text-brand-cyan' => request()->routeIs('legal.terms')])
            >{{ __('ui.terms') }}</a>
            <a
                href="{{ route('legal.delete-account') }}"
                @class(['hover:text-brand-cyan', 'text-brand-cyan' => request()->routeIs('legal.delete-account')])
            >{{ __('ui.delete_account') }}</a>
        </nav>
        <div class="flex items-center gap-3">
            <x-ui.locale-switcher />
            <x-ui.theme-toggle />
            <a href="{{ route('login') }}" class="hidden text-sm text-brand-muted hover:text-brand-cyan sm:inline">{{ __('ui.partner_login') }}</a>
        </div>
    </div>
</header>
