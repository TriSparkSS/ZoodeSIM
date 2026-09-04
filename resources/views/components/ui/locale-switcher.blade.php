<div class="relative" x-data="{ open: false }">
    <button
        type="button"
        @click="open = !open"
        class="flex items-center gap-1.5 rounded-lg border border-surface-border px-3 py-1.5 text-xs font-medium text-surface-muted transition-colors hover:border-brand-cyan/40 hover:text-brand-cyan dark:border-brand-border dark:text-brand-muted"
        :aria-expanded="open"
        aria-haspopup="listbox"
        aria-label="{{ __('ui.language') }}"
    >
        🌐 {{ config('locales.supported.'.app()->getLocale().'.native', 'EN') }}
    </button>

    <div
        x-show="open"
        @click.outside="open = false"
        x-cloak
        role="listbox"
        class="absolute end-0 z-50 mt-2 max-h-64 w-44 overflow-y-auto rounded-xl border border-surface-border bg-surface-card py-1 shadow-lg dark:border-brand-border dark:bg-brand-card"
    >
        @foreach(config('locales.supported', []) as $code => $locale)
            <a
                href="{{ route('locale.switch', ['locale' => $code]) }}"
                role="option"
                @class([
                    'block px-4 py-2 text-sm transition-colors hover:bg-brand-cyan/5',
                    'font-semibold text-brand-cyan' => app()->getLocale() === $code,
                    'text-surface-muted dark:text-brand-muted' => app()->getLocale() !== $code,
                ])
                @if(app()->getLocale() === $code) aria-selected="true" @endif
            >
                {{ $locale['native'] }}
            </a>
        @endforeach
    </div>
</div>
