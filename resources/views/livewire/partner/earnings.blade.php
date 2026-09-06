<div>
    <x-ui.page-header
        :title="__('partner.earnings.title')"
        :subtitle="__('partner.earnings.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <!-- Balance Stats -->
    <div class="mb-7 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat-card
            :label="__('partner.earnings.available')"
            :value="'$'.number_format($stats['available_withdrawal'], 2)"
            :change="__('partner.dashboard.min_withdrawal')"
            change-type="down"
            color="green"
        />
        <x-ui.stat-card
            :label="__('partner.earnings.pending')"
            :value="'$'.number_format($pendingAmount, 2)"
            color="yellow"
        />
        <x-ui.stat-card
            :label="__('partner.earnings.lifetime')"
            :value="'$'.number_format($lifetimeAmount, 2)"
            :change="'↑ '.__('partner.dashboard.today', ['amount' => '$34.50'])"
            color="cyan"
        />
    </div>

    <!-- Request Withdrawal -->
    <div class="mb-7 flex flex-col items-start justify-between gap-5 rounded-2xl border border-surface-border bg-gradient-to-br from-brand-green/8 to-brand-cyan/8 p-6 dark:border-brand-border md:flex-row md:items-center">
        <div>
            <h3 class="mb-1 text-sm text-surface-muted dark:text-brand-muted">{{ __('partner.earnings.available') }}</h3>
            <div class="text-4xl font-black text-brand-green">${{ number_format($stats['available_withdrawal'], 2) }}</div>
            <p class="mt-2 text-sm text-surface-muted dark:text-brand-muted">{{ __('partner.dashboard.min_withdrawal') }}</p>
        </div>
        <x-ui.button variant="success" size="lg" wire:click="requestWithdrawal">
            {{ __('partner.earnings.request_withdrawal') }}
        </x-ui.button>
    </div>

    <div class="mb-7 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <!-- Earnings History -->
        <x-ui.card :title="__('partner.earnings.history')">
            @if(count($earningsHistory) === 0)
                <x-ui.empty-state :title="__('ui.no_data')" :description="__('partner.earnings.subtitle')" />
            @else
            <div class="overflow-x-auto">
                <table class="w-full table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('partner.earnings.table_source') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.date') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('ui.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($earningsHistory as $entry)
                            <tr class="text-sm text-surface-text dark:text-brand-text">
                                <td class="py-3.5 pe-4">
                                    <div class="font-semibold">{{ $entry['description'] }}</div>
                                    <div class="text-[11px] text-surface-muted dark:text-brand-muted">
                                        {{ $entry['type'] === 'registration' ? __('partner.earnings.type_registration') : __('partner.earnings.type_purchase') }}
                                    </div>
                                </td>
                                <td class="py-3.5 pe-4 text-surface-muted dark:text-brand-muted">{{ $entry['date'] }}</td>
                                <td class="py-3.5 font-bold text-brand-green">+${{ number_format($entry['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </x-ui.card>

        <!-- Withdrawal History -->
        <x-ui.card :title="__('partner.earnings.withdrawals')">
            @if(count($withdrawals) === 0)
                <x-ui.empty-state :title="__('ui.no_data')" :description="__('partner.earnings.subtitle')" />
            @else
            <div class="overflow-x-auto">
                <table class="w-full table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.date') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.amount') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('partner.earnings.payment_method') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('ui.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($withdrawals as $withdrawal)
                            <tr class="text-sm text-surface-text dark:text-brand-text">
                                <td class="py-3.5 pe-4 text-surface-muted dark:text-brand-muted">{{ $withdrawal['date'] }}</td>
                                <td class="py-3.5 pe-4 font-bold">${{ number_format($withdrawal['amount'], 2) }}</td>
                                <td class="py-3.5 pe-4">
                                    {{ strtolower((string) $withdrawal['method']) === 'paypal' ? __('partner.earnings.paypal') : __('partner.earnings.bank_transfer') }}
                                </td>
                                <td class="py-3.5">
                                    @php
                                        $badgeType = match ($withdrawal['status']) {
                                            'completed' => 'completed',
                                            'approved' => 'approved',
                                            default => 'pending',
                                        };
                                        $statusLabel = match ($withdrawal['status']) {
                                            'completed' => __('ui.status_completed'),
                                            'approved' => __('ui.status_approved'),
                                            default => __('ui.status_pending'),
                                        };
                                    @endphp
                                    <x-ui.badge :type="$badgeType">{{ $statusLabel }}</x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </x-ui.card>
    </div>
</div>
