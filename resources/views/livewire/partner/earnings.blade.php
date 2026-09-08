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
            :change="__('partner.dashboard.min_withdrawal', ['amount' => $minWithdrawal])"
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
            color="cyan"
        />
    </div>

    <!-- Request Withdrawal -->
    <div class="mb-7 flex flex-col items-start justify-between gap-5 rounded-2xl border border-surface-border bg-gradient-to-br from-brand-green/8 to-brand-cyan/8 p-6 dark:border-brand-border md:flex-row md:items-center">
        <div>
            <h3 class="mb-1 text-sm text-surface-muted dark:text-brand-muted">{{ __('partner.earnings.available') }}</h3>
            <div class="text-4xl font-black text-brand-green">${{ number_format($stats['available_withdrawal'], 2) }}</div>
            <p class="mt-2 text-sm text-surface-muted dark:text-brand-muted">{{ __('partner.dashboard.min_withdrawal', ['amount' => $minWithdrawal]) }}</p>
        </div>
        <x-ui.button variant="success" size="lg" wire:click="openWithdrawModal">
            {{ __('partner.earnings.request_withdrawal') }}
        </x-ui.button>
    </div>

    <div class="mb-7 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <!-- Earnings History -->
        <x-ui.card :title="__('partner.earnings.history')">
            <div class="mb-4">
                <x-ui.filter-tabs
                    :active="$filter"
                    :tabs="[
                        'today' => __('partner.earnings.filter_today'),
                        'week' => __('partner.earnings.filter_week'),
                        'month' => __('partner.earnings.filter_month'),
                        'all' => __('partner.earnings.filter_all'),
                    ]"
                />
            </div>
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
                                        {{ match ($entry['type']) {
                                            'purchase' => __('partner.earnings.type_purchase'),
                                            'milestone' => __('partner.earnings.type_milestone'),
                                            default => __('partner.earnings.type_registration'),
                                        } }}
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
                                    {{ \App\Models\Withdrawal::methodLabel((string) $withdrawal['method']) }}
                                </td>
                                <td class="py-3.5">
                                    @php
                                        $badgeType = match ($withdrawal['status']) {
                                            'completed' => 'completed',
                                            'rejected', 'failed' => 'rejected',
                                            'processing' => 'approved',
                                            default => 'pending',
                                        };
                                        $statusLabel = match ($withdrawal['status']) {
                                            'completed' => __('ui.status_completed'),
                                            'rejected' => __('ui.status_rejected'),
                                            'failed' => __('ui.status_failed'),
                                            'processing' => __('ui.status_processing'),
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

    <x-ui.card :title="__('partner.earnings.transactions')">
        @if(count($walletTransactions) === 0)
            <x-ui.empty-state
                :title="__('ui.no_data')"
                :description="__('partner.earnings.transactions_empty')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_id') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_type') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_category') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('ui.amount') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_before') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_after') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_promo') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('ui.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($walletTransactions as $row)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="partner-txn-{{ $row['id'] }}">
                                <td class="py-3 pe-3 align-middle font-mono text-xs font-semibold">{{ $row['transaction_id'] }}</td>
                                <td class="py-3 pe-3 align-middle">
                                    <span @class([
                                        'font-semibold',
                                        'text-brand-green' => $row['type'] === 'credit',
                                        'text-brand-red' => $row['type'] !== 'credit',
                                    ])>
                                        {{ __('admin.transactions.types.'.$row['type']) }}
                                    </span>
                                </td>
                                <td class="py-3 pe-3 align-middle">{{ __('admin.partners.wallet.categories.'.$row['category']) }}</td>
                                <td class="py-3 pe-3 align-middle font-semibold">
                                    {{ $row['type'] === 'credit' ? '+' : '−' }}
                                    {{ $row['currency'] === 'MB' ? number_format($row['amount'], 0).' MB' : '$'.number_format($row['amount'], 2) }}
                                </td>
                                <td class="py-3 pe-3 align-middle text-surface-muted dark:text-brand-muted">
                                    {{ $row['currency'] === 'MB' ? number_format($row['balance_before'], 0).' MB' : '$'.number_format($row['balance_before'], 2) }}
                                </td>
                                <td class="py-3 pe-3 align-middle font-semibold">
                                    {{ $row['currency'] === 'MB' ? number_format($row['balance_after'], 0).' MB' : '$'.number_format($row['balance_after'], 2) }}
                                </td>
                                <td class="py-3 pe-3 align-middle text-xs">
                                    {{ $row['promo'] ?? '—' }}
                                </td>
                                <td class="py-3 align-middle text-xs text-surface-muted dark:text-brand-muted">
                                    {{ $row['date'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    @include('livewire.partner.partials.withdraw-modal')
</div>
