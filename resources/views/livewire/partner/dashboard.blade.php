<div
    x-data
    @copy-to-clipboard.window="navigator.clipboard.writeText($event.detail.text).catch(() => {})"
>
    <x-ui.page-header
        :title="__('partner.dashboard.title')"
        :subtitle="__('partner.dashboard.subtitle', ['name' => $partnerName])"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <span class="rounded-lg border border-brand-border bg-brand-card px-4 py-2 text-sm text-brand-muted dark:bg-brand-card">
                📅 {{ now()->translatedFormat('F Y') }}
            </span>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Promo Card -->
    <div class="gradient-border mb-7 flex flex-col items-start justify-between gap-5 rounded-2xl p-6 md:flex-row md:items-center">
        <div>
            <h3 class="mb-2 text-sm text-brand-muted">{{ __('partner.dashboard.your_promo_code') }}</h3>
            <div class="text-4xl font-black tracking-[0.15em] gradient-text">{{ $stats['promo_code'] }}</div>
            <p class="mt-2 text-sm text-brand-muted">
                {!! __('partner.dashboard.promo_description', [
                    'bonus' => '<span class="font-semibold text-brand-green">'.$stats['promo_bonus'].'</span>',
                    'reward' => '<span class="font-semibold text-brand-green">'.$stats['promo_reward'].'</span>',
                ]) !!}
            </p>
        </div>
        <x-ui.button wire:click="copyPromoCode" size="lg">
            📋 {{ __('partner.dashboard.copy_code') }}
        </x-ui.button>
    </div>

    <!-- Stats Grid -->
    <div class="mb-7 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            :label="__('partner.dashboard.stats_registrations')"
            :value="(string) $stats['registrations']"
            :change="'↑ '.__('partner.dashboard.this_week', ['count' => 23])"
            color="cyan"
        />
        <x-ui.stat-card
            :label="__('partner.dashboard.stats_earnings')"
            :value="'$'.$stats['earnings']"
            :change="'↑ '.__('partner.dashboard.today', ['amount' => '$34.50'])"
            color="green"
        />
        <x-ui.stat-card
            :label="__('partner.dashboard.stats_active_users')"
            :value="(string) $stats['active_users']"
            :change="'↑ '.__('partner.dashboard.conversion', ['rate' => $stats['conversion']])"
            color="purple"
        />
        <x-ui.stat-card
            :label="__('partner.dashboard.stats_available')"
            :value="'$'.$stats['available_withdrawal']"
            :change="__('partner.dashboard.min_withdrawal')"
            change-type="down"
            color="white"
        />
    </div>

    <!-- Two Columns -->
    <div class="mb-7 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <!-- Chart -->
        <x-ui.card :title="__('partner.dashboard.registrations_chart')" :action="now()->translatedFormat('F').' →'" :action-href="route('partner.statistics')">
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

        <!-- Earnings -->
        <x-ui.card :title="__('partner.dashboard.earnings_breakdown')">
            <div class="space-y-0 divide-y divide-brand-border">
                <div class="flex items-center justify-between py-3.5 text-sm">
                    <span class="text-brand-muted">{{ __('partner.dashboard.per_registration') }}</span>
                    <span class="font-bold">$1.50 / {{ __('ui.actions') === 'Actions' ? 'user' : 'user' }}</span>
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
                    <span class="text-brand-muted">{{ __('partner.dashboard.already_withdrawn') }}</span>
                    <span class="font-bold text-brand-muted">$228.00</span>
                </div>
            </div>
            <x-ui.button variant="success" class="mt-4 w-full" wire:click="requestWithdrawal">
                💳 {{ __('partner.dashboard.withdraw', ['amount' => '$'.number_format($stats['available_withdrawal'], 2)]) }}
            </x-ui.button>
        </x-ui.card>
    </div>

    <!-- Recent Registrations -->
    <x-ui.card :title="__('partner.dashboard.recent_registrations')" :action="__('ui.view_all')" :action-href="route('partner.registrations')" class="mb-7">
        <div class="space-y-2.5">
            @foreach($registrations as $reg)
                <div class="flex items-center justify-between rounded-xl bg-brand-card-alt px-3.5 py-3 dark:bg-brand-card-alt">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br {{ $reg['gradient'] }} text-xs font-bold text-white">
                            {{ $reg['initial'] }}
                        </div>
                        <div>
                            <div class="text-sm font-semibold">{{ $reg['name'] }}</div>
                            <div class="text-[11px] text-brand-muted">
                                {{ __('ui.time.'.$reg['time']) }} · {{ __('partner.dashboard.used_code', ['code' => $reg['code']]) }}
                            </div>
                        </div>
                    </div>
                    <x-ui.badge type="approved">+{{ $reg['bonus'] }}</x-ui.badge>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    <!-- Promo Codes Table -->
    <x-ui.card :title="__('partner.dashboard.my_promo_codes')" :action="'+ '.__('partner.dashboard.create_new')" :action-href="route('partner.promo-codes')">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-brand-border text-start text-[11px] uppercase tracking-wide text-brand-muted">
                        <th class="pb-3.5 pe-4">{{ __('partner.dashboard.table_code') }}</th>
                        <th class="pb-3.5 pe-4">{{ __('partner.dashboard.table_uses') }}</th>
                        <th class="pb-3.5 pe-4">{{ __('partner.dashboard.table_bonus') }}</th>
                        <th class="pb-3.5 pe-4">{{ __('partner.dashboard.table_earnings') }}</th>
                        <th class="pb-3.5">{{ __('ui.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border/40">
                    @foreach($promoCodes as $promo)
                        <tr class="text-sm">
                            <td class="py-3.5 pe-4">
                                <strong class="tracking-widest text-brand-cyan">{{ $promo['code'] }}</strong>
                            </td>
                            <td class="py-3.5 pe-4">{{ $promo['uses'] }}</td>
                            <td class="py-3.5 pe-4">{{ $promo['bonus'] }}</td>
                            <td class="py-3.5 pe-4">${{ number_format($promo['earnings'], 2) }}</td>
                            <td class="py-3.5">
                                <x-ui.badge :type="$promo['status'] === 'active' ? 'active' : 'pending'">
                                    {{ $promo['status'] === 'active' ? __('ui.status_active') : __('ui.status_pending') }}
                                </x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
