<div>
    <x-ui.page-header
        :title="__('partner.promo_codes.title')"
        :subtitle="__('partner.promo_codes.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <x-ui.card :title="__('partner.dashboard.my_promo_codes')">
        @if(count($promoCodes) === 0)
            <x-ui.empty-state
                :title="__('ui.no_data')"
                :description="__('partner.promo_codes.empty')"
            />
        @else
            {{-- Mobile --}}
            <div class="space-y-3 md:hidden">
                @foreach($promoCodes as $promo)
                    <div
                        class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                        wire:key="partner-promo-mobile-{{ $promo['id'] }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <strong class="tracking-widest text-brand-cyan">{{ $promo['code'] }}</strong>
                            @include('livewire.partner.partials.promo-status-badge')
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('partner.dashboard.table_uses') }}</div>
                                <div class="mt-1 font-medium text-surface-text dark:text-brand-text">{{ $promo['uses_label'] }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('partner.dashboard.table_bonus') }}</div>
                                <div class="mt-1 font-medium text-surface-text dark:text-brand-text">{{ $promo['bonus'] }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('partner.dashboard.table_earnings') }}</div>
                                <div class="mt-1 font-medium text-surface-text dark:text-brand-text">${{ number_format($promo['earnings'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[720px] table-fixed border-collapse text-start">
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
                        @foreach($promoCodes as $promo)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="partner-promo-{{ $promo['id'] }}">
                                <td class="py-3.5 pe-4 align-middle">
                                    <strong class="tracking-widest text-brand-cyan">{{ $promo['code'] }}</strong>
                                </td>
                                <td class="py-3.5 pe-4 align-middle">{{ $promo['uses_label'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $promo['bonus'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">${{ number_format($promo['earnings'], 2) }}</td>
                                <td class="py-3.5 align-middle">
                                    @include('livewire.partner.partials.promo-status-badge')
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
