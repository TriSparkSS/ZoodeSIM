<button
    type="button"
    x-data
    @click="
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.theme = isDark ? 'dark' : 'light';
    "
    class="flex h-9 w-9 items-center justify-center rounded-lg border border-surface-border text-sm transition-colors hover:border-brand-cyan/40 dark:border-brand-border"
    aria-label="{{ __('ui.theme') }}"
>
    <span class="hidden dark:inline">☀️</span>
    <span class="dark:hidden">🌙</span>
</button>
