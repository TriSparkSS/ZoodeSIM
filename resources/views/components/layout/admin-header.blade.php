<div class="border-b border-brand-border bg-brand-card px-6 md:px-8">
    <div class="flex items-center gap-0 overflow-x-auto">
        <a
            href="{{ route('apply') }}"
            @class([
                'shrink-0 border-b-[3px] px-5 py-4 text-sm font-semibold transition-colors md:px-7',
                'border-brand-cyan text-brand-cyan' => request()->routeIs('apply'),
                'border-transparent text-brand-muted hover:text-brand-text' => ! request()->routeIs('apply'),
            ])
        >
            📱 {{ __('apply.form.title') }}
        </a>
        <a
            href="{{ route('admin.login') }}"
            @class([
                'shrink-0 border-b-[3px] px-5 py-4 text-sm font-semibold transition-colors md:px-7',
                'border-brand-cyan text-brand-cyan' => request()->routeIs('admin.*'),
                'border-transparent text-brand-muted hover:text-brand-text' => ! request()->routeIs('admin.*'),
            ])
        >
            🛡️ {{ __('ui.admin_panel') }}
        </a>
    </div>
</div>
