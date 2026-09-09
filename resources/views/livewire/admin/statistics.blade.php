<div>
    <x-ui.page-header
        :title="__('admin.statistics.title')"
        :subtitle="__('admin.statistics.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    @include('livewire.admin.partials.resellportal-balance')
    @include('livewire.admin.partials.statistics-summary-cards')

    <div class="mb-7 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-ui.card :title="__('admin.statistics.monthly_trend')">
            <div class="mb-4 flex items-center gap-4 text-xs text-surface-muted dark:text-brand-muted">
                <span class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-sm bg-brand-cyan"></span>
                    {{ __('partner.statistics.registrations_count') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-sm bg-brand-purple"></span>
                    {{ __('partner.statistics.earnings_total') }}
                </span>
            </div>
            <div class="mb-3 flex h-24 items-end gap-2">
                @foreach($monthlyTrend as $month)
                    @php
                        $regHeight = $maxRegistrations > 0 ? ($month['registrations'] / $maxRegistrations) * 100 : 0;
                        $earnHeight = $maxEarnings > 0 ? ($month['earnings'] / $maxEarnings) * 100 : 0;
                    @endphp
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <div class="flex w-full items-end gap-0.5" style="height: 80px">
                            <div
                                class="flex-1 rounded-t bg-brand-cyan opacity-70 transition-opacity hover:opacity-100"
                                style="height: {{ max($regHeight, $month['registrations'] > 0 ? 4 : 0) }}%"
                                title="{{ number_format($month['registrations']) }}"
                            ></div>
                            <div
                                class="flex-1 rounded-t bg-brand-purple opacity-70 transition-opacity hover:opacity-100"
                                style="height: {{ max($earnHeight, $month['earnings'] > 0 ? 4 : 0) }}%"
                                title="${{ number_format($month['earnings'], 2) }}"
                            ></div>
                        </div>
                        <span class="text-[10px] text-surface-muted dark:text-brand-muted">
                            {{ $month['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card :title="__('admin.statistics.top_partners')">
            @if(count($topPartners) === 0)
                <x-ui.empty-state
                    :title="__('ui.no_data')"
                    :description="__('ui.empty_state_description')"
                />
            @else
                <div class="space-y-2.5">
                    @foreach($topPartners as $partner)
                        <div class="flex items-center justify-between rounded-xl border border-surface-border bg-surface-card-alt px-3.5 py-3 dark:border-transparent dark:bg-brand-card-alt">
                            <div>
                                <div class="text-sm font-semibold text-surface-text dark:text-brand-text">{{ $partner['name'] }}</div>
                                <div class="text-[11px] text-surface-muted dark:text-brand-muted">{{ $partner['promo'] ?? '—' }}</div>
                            </div>
                            <div class="text-end">
                                <div class="text-sm font-bold text-brand-cyan">{{ number_format($partner['registrations']) }}</div>
                                <div class="text-[11px] text-surface-muted dark:text-brand-muted">${{ number_format($partner['earnings'], 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    @include('livewire.admin.partials.recent-applications')
</div>
