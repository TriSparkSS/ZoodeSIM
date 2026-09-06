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
            <span class="rounded-lg border border-surface-border bg-surface-card px-4 py-2 text-sm text-surface-muted dark:border-brand-border dark:bg-brand-card dark:text-brand-muted">
                {{ now()->translatedFormat('F Y') }}
            </span>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-7 flex flex-col items-start justify-between gap-5 rounded-2xl border border-surface-border bg-gradient-to-br from-brand-cyan/8 to-brand-purple/8 p-6 dark:border-brand-border md:flex-row md:items-center">
        <div>
            <h3 class="mb-2 text-sm text-surface-muted dark:text-brand-muted">{{ __('partner.dashboard.your_promo_code') }}</h3>
            <div class="text-4xl font-black tracking-[0.15em] gradient-text">{{ $stats['promo_code'] }}</div>
            @if($stats['promo_code'] !== '—')
                <p class="mt-2 text-sm text-surface-muted dark:text-brand-muted">
                    {!! __('partner.dashboard.promo_description', [
                        'bonus' => '<span class="font-semibold text-brand-green">'.$stats['promo_bonus'].'</span>',
                        'reward' => '<span class="font-semibold text-brand-green">'.$stats['promo_reward'].'</span>',
                    ]) !!}
                </p>
            @else
                <p class="mt-2 text-sm text-surface-muted dark:text-brand-muted">{{ __('partner.promo_codes.empty') }}</p>
            @endif
        </div>
        @if($stats['promo_code'] !== '—')
            <x-ui.button wire:click="copyPromoCode" size="lg">
                {{ __('partner.dashboard.copy_code') }}
            </x-ui.button>
        @endif
    </div>

    <div class="mb-7 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card
            :label="__('partner.dashboard.stats_registrations')"
            :value="(string) $stats['registrations']"
            color="cyan"
        />
        <x-ui.stat-card
            :label="__('partner.dashboard.stats_earnings')"
            :value="'$'.number_format($stats['earnings'], 2)"
            color="green"
        />
        <x-ui.stat-card
            :label="__('partner.dashboard.stats_active_users')"
            :value="(string) $stats['active_users']"
            color="purple"
        />
        <x-ui.stat-card
            :label="__('partner.dashboard.stats_available')"
            :value="'$'.number_format($stats['available_withdrawal'], 2)"
            color="white"
        />
    </div>

    <div class="mb-7 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-ui.card :title="__('partner.dashboard.earnings_breakdown')">
            <div class="space-y-0 divide-y divide-surface-border dark:divide-brand-border/40">
                <div class="flex items-center justify-between py-3.5 text-sm">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('partner.dashboard.per_registration') }}</span>
                    <span class="font-bold text-surface-text dark:text-brand-text">{{ $stats['promo_reward'] }}</span>
                </div>
                <div class="flex items-center justify-between py-3.5 text-sm">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('partner.dashboard.total_earned') }}</span>
                    <span class="text-2xl font-bold text-brand-green">${{ number_format($stats['earnings'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between py-3.5 text-sm">
                    <span class="text-surface-muted dark:text-brand-muted">{{ __('partner.dashboard.already_withdrawn') }}</span>
                    <span class="font-bold text-surface-muted dark:text-brand-muted">${{ number_format($stats['withdrawn'], 2) }}</span>
                </div>
            </div>
            <x-ui.button variant="success" class="mt-4 w-full" wire:click="requestWithdrawal">
                {{ __('partner.dashboard.withdraw', ['amount' => '$'.number_format($stats['available_withdrawal'], 2)]) }}
            </x-ui.button>
        </x-ui.card>

        <x-ui.card :title="__('partner.dashboard.recent_registrations')" :action="__('ui.view_all')" :action-href="route('partner.registrations')">
            @if(count($registrations) === 0)
                <x-ui.empty-state
                    :title="__('ui.no_data')"
                    :description="__('partner.registrations.subtitle')"
                />
            @else
                <div class="space-y-2.5">
                    @foreach(array_slice($registrations, 0, 5) as $reg)
                        <div class="flex items-center justify-between rounded-xl border border-surface-border bg-surface-card-alt/70 px-3.5 py-3 dark:border-transparent dark:bg-brand-card-alt">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br {{ $reg['gradient'] }} text-xs font-bold text-white">
                                    {{ $reg['initial'] }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-surface-text dark:text-brand-text">{{ $reg['name'] }}</div>
                                    <div class="text-[11px] text-surface-muted dark:text-brand-muted">
                                        {{ $reg['time'] }} · {{ __('partner.dashboard.used_code', ['code' => $reg['code']]) }}
                                    </div>
                                </div>
                            </div>
                            <x-ui.badge type="approved">+{{ $reg['bonus'] }}</x-ui.badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card :title="__('partner.dashboard.my_promo_codes')" :action="__('ui.view_all')" :action-href="route('partner.promo-codes')">
        @if(count($promoCodes) === 0)
            <x-ui.empty-state
                :title="__('ui.no_data')"
                :description="__('partner.promo_codes.empty')"
            />
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[640px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('partner.dashboard.table_code') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('partner.dashboard.table_uses') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('partner.dashboard.table_bonus') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('partner.dashboard.table_earnings') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('ui.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach(array_slice($promoCodes, 0, 5) as $promo)
                            <tr class="text-sm text-surface-text dark:text-brand-text">
                                <td class="py-3.5 pe-4 align-middle">
                                    <strong class="tracking-widest text-brand-cyan">{{ $promo['code'] }}</strong>
                                </td>
                                <td class="py-3.5 pe-4 align-middle">{{ number_format($promo['uses']) }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $promo['bonus'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">${{ number_format($promo['earnings'], 2) }}</td>
                                <td class="py-3.5 align-middle">
                                    <x-ui.badge :type="$promo['status']">
                                        @if($promo['status'] === 'active')
                                            {{ __('ui.status_active') }}
                                        @elseif($promo['status'] === 'expired')
                                            {{ __('ui.status_expired') }}
                                        @elseif($promo['status'] === 'exhausted')
                                            {{ __('ui.status_exhausted') }}
                                        @else
                                            {{ __('ui.status_inactive') }}
                                        @endif
                                    </x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 md:hidden">
                @foreach(array_slice($promoCodes, 0, 5) as $promo)
                    <div class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40">
                        <div class="flex items-center justify-between gap-3">
                            <strong class="tracking-widest text-brand-cyan">{{ $promo['code'] }}</strong>
                            <x-ui.badge :type="$promo['status']">
                                {{ $promo['status'] === 'active' ? __('ui.status_active') : __('ui.status_'.$promo['status']) }}
                            </x-ui.badge>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-sm text-surface-text dark:text-brand-text">
                            <div>{{ number_format($promo['uses']) }} uses</div>
                            <div>{{ $promo['bonus'] }}</div>
                            <div>${{ number_format($promo['earnings'], 2) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>
</div>
