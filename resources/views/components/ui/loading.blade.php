@props(['text' => null])

<div {{ $attributes->merge(['class' => 'flex items-center justify-center py-16']) }}>
    <div class="flex flex-col items-center gap-3">
        <div class="h-10 w-10 animate-spin rounded-full border-2 border-brand-cyan border-t-transparent"></div>
        <span class="text-sm text-brand-muted">{{ $text ?? __('ui.loading') }}</span>
    </div>
</div>
