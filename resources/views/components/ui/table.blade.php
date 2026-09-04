@props([
    'headers' => [],
])

<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="w-full min-w-[640px] table-fixed border-collapse text-start">
        @if(count($headers) > 0)
            <thead>
                <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                    @foreach($headers as $header)
                        <th @class([
                            'pb-3.5 text-start font-medium',
                            'pe-4' => ! $loop->last,
                        ])>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
            {{ $slot }}
        </tbody>
    </table>
</div>
