<div class="mx-auto max-w-3xl px-6 py-6">
    <nav class="mb-5 flex flex-wrap gap-2 md:hidden" aria-label="{{ __('ui.legal.documents') }}">
        @foreach($documentLinks as $link)
            <a
                href="{{ $link['href'] }}"
                @class([
                    'rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                    'border-brand-cyan bg-brand-cyan/10 text-brand-cyan' => $link['active'],
                    'border-surface-border text-surface-muted hover:border-brand-cyan/40 hover:text-brand-cyan dark:border-brand-border dark:text-brand-muted' => ! $link['active'],
                ])
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    <header class="mb-5">
        <h1 class="text-2xl font-bold tracking-tight text-surface-text dark:text-brand-text">{{ $heading }}</h1>
        <p class="mt-1.5 text-sm leading-relaxed text-surface-muted dark:text-brand-muted">{{ $intro }}</p>
        @if($updatedAt !== '')
            <p class="mt-1 text-xs text-surface-muted dark:text-brand-muted">{{ __('ui.legal.last_updated', ['date' => $updatedAt]) }}</p>
        @endif
    </header>

    <article class="light-card px-6 py-6">
        <div class="space-y-4">
            @foreach($sections as $index => $section)
                @if($section['type'] === 'heading')
                    <h2 class="border-s-2 border-brand-cyan ps-3 text-base font-semibold tracking-tight text-surface-text dark:text-brand-text">
                        {{ $section['text'] }}
                    </h2>
                @elseif($section['type'] === 'list')
                    <ul class="space-y-1.5 text-sm leading-relaxed text-surface-muted dark:text-brand-muted">
                        @foreach($section['items'] as $item)
                            <li class="flex gap-2.5">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-cyan"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p @class([
                        'text-sm leading-6 text-surface-muted dark:text-brand-muted',
                        'font-medium text-surface-text dark:text-brand-text' => $index === 0 && ($sections[1]['type'] ?? '') === 'heading',
                    ])>
                        {{ $section['text'] }}
                    </p>
                @endif
            @endforeach
        </div>
    </article>

    <p class="mt-4 text-xs leading-relaxed text-surface-muted dark:text-brand-muted">
        {{ __('ui.legal.disclaimer') }}
    </p>
</div>
