<div>
    <x-ui.page-header
        :title="__('admin.statistics.title')"
        :subtitle="__('admin.statistics.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <div class="mb-7 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            :label="__('admin.statistics.total_partners')"
            :value="(string) $stats['total_partners']"
            color="cyan"
        />
        <x-ui.stat-card
            :label="__('admin.statistics.total_registrations')"
            :value="number_format($stats['total_registrations'])"
            color="green"
        />
        <x-ui.stat-card
            :label="__('admin.statistics.total_payouts')"
            :value="'$'.number_format($stats['total_payouts'], 2)"
            color="purple"
        />
        <x-ui.stat-card
            :label="__('admin.statistics.active_codes')"
            :value="(string) $stats['active_codes']"
            color="yellow"
        />
    </div>

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

    <x-ui.card :title="__('admin.statistics.recent_applications')">
        @if(count($recentApplications) === 0)
            <x-ui.empty-state
                :title="__('ui.no_data')"
                :description="__('ui.empty_state_description')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_blogger') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_platforms') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.applications.table_followers') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('ui.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($recentApplications as $application)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="app-{{ $application['id'] }}">
                                <td class="py-3.5 pe-4 align-middle">
                                    <div class="font-semibold">{{ $application['name'] }}</div>
                                    <div class="text-xs text-surface-muted dark:text-brand-muted">{{ $application['email'] }}</div>
                                </td>
                                <td class="py-3.5 pe-4 align-middle">
                                    @forelse($application['platforms'] as $platform)
                                        <span class="text-[11px]">{{ __('ui.platforms.'.$platform) }}@if(! $loop->last), @endif</span>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td class="py-3.5 pe-4 align-middle">
                                    {{ $application['followers'] ? __('ui.followers.'.$application['followers']) : '—' }}
                                </td>
                                <td class="py-3.5 align-middle">
                                    <x-ui.badge :type="$application['status']">
                                        {{ __('ui.status_'.$application['status']) }}
                                    </x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
