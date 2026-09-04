<div>
    <x-ui.page-header
        :title="__('partner.statistics.title')"
        :subtitle="__('partner.statistics.subtitle')"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <span class="rounded-lg border border-brand-border bg-brand-card px-4 py-2 text-sm text-brand-muted dark:bg-brand-card">
                📅 {{ now()->translatedFormat('F Y') }}
            </span>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Analytics Stats -->
    <div class="mb-7 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            :label="__('partner.dashboard.stats_registrations')"
            :value="(string) $stats['registrations']"
            :change="'↑ '.__('partner.dashboard.this_week', ['count' => 23])"
            color="cyan"
        />
        <x-ui.stat-card
            :label="__('partner.statistics.conversion_rate')"
            :value="$stats['conversion']"
            :change="'↑ '.__('partner.dashboard.conversion', ['rate' => $stats['conversion']])"
            color="purple"
        />
        <x-ui.stat-card
            :label="__('partner.statistics.avg_earnings')"
            :value="'$'.number_format($avgEarnings, 2)"
            color="green"
        />
        <x-ui.stat-card
            :label="__('partner.statistics.top_code')"
            :value="$topCode['code']"
            :change="__('partner.dashboard.table_uses').': '.$topCode['uses']"
            change-type="neutral"
            color="white"
        />
    </div>

    <div class="mb-7 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <!-- Daily Bar Chart -->
        <x-ui.card :title="__('partner.dashboard.registrations_chart')">
            <div class="mb-3 flex h-20 items-end gap-1.5">
                @foreach($chartData as $bar)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <div
                            class="w-full rounded-t bg-gradient-to-t from-brand-purple to-brand-cyan opacity-70 transition-opacity hover:opacity-100"
                            style="height: {{ $bar['value'] }}%"
                        ></div>
                        <span class="text-[10px] text-brand-muted">{{ __('ui.days.'.$bar['label']) }}</span>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-brand-muted">
                {{ __('partner.dashboard.week_total', ['count' => 23]) }}
            </p>
        </x-ui.card>

        <!-- Monthly Trend -->
        <x-ui.card :title="__('partner.statistics.monthly_trend')">
            <div class="mb-4 flex items-center gap-4 text-xs text-brand-muted">
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
                        $maxRegistrations = collect($monthlyTrend)->max('registrations');
                        $regHeight = $maxRegistrations > 0 ? ($month['registrations'] / $maxRegistrations) * 100 : 0;
                    @endphp
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <div class="flex w-full items-end gap-0.5" style="height: 80px">
                            <div
                                class="flex-1 rounded-t bg-brand-cyan opacity-70 transition-opacity hover:opacity-100"
                                style="height: {{ $regHeight }}%"
                            ></div>
                            <div
                                class="flex-1 rounded-t bg-brand-purple opacity-70 transition-opacity hover:opacity-100"
                                style="height: {{ min(($month['earnings'] / 60) * 100, 100) }}%"
                            ></div>
                        </div>
                        <span class="text-[10px] text-brand-muted">
                            {{ \Carbon\Carbon::create()->month($month['month'])->translatedFormat('M') }}
                        </span>
                    </div>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div class="rounded-lg bg-brand-card-alt px-3 py-2 dark:bg-brand-card-alt">
                    <span class="text-brand-muted">{{ __('partner.statistics.registrations_count') }}</span>
                    <div class="font-bold text-brand-cyan">{{ collect($monthlyTrend)->sum('registrations') }}</div>
                </div>
                <div class="rounded-lg bg-brand-card-alt px-3 py-2 dark:bg-brand-card-alt">
                    <span class="text-brand-muted">{{ __('partner.statistics.earnings_total') }}</span>
                    <div class="font-bold text-brand-purple">${{ number_format(collect($monthlyTrend)->sum('earnings'), 2) }}</div>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Earnings Overview -->
    <x-ui.card :title="__('partner.dashboard.earnings_breakdown')">
        <div class="space-y-0 divide-y divide-brand-border">
            <div class="flex items-center justify-between py-3.5 text-sm">
                <span class="text-brand-muted">{{ __('partner.dashboard.per_registration') }}</span>
                <span class="font-bold">$1.50</span>
            </div>
            <div class="flex items-center justify-between py-3.5 text-sm">
                <span class="text-brand-muted">{{ __('partner.dashboard.per_purchase') }}</span>
                <span class="font-bold">{{ __('partner.dashboard.commission', ['rate' => '10%']) }}</span>
            </div>
            <div class="flex items-center justify-between py-3.5 text-sm">
                <span class="text-brand-muted">{{ __('partner.dashboard.total_earned') }}</span>
                <span class="text-2xl font-bold text-brand-green">${{ number_format($stats['earnings'], 2) }}</span>
            </div>
            <div class="flex items-center justify-between py-3.5 text-sm">
                <span class="text-brand-muted">{{ __('partner.dashboard.stats_active_users') }}</span>
                <span class="font-bold text-brand-purple">{{ $stats['active_users'] }}</span>
            </div>
        </div>
    </x-ui.card>
</div>
