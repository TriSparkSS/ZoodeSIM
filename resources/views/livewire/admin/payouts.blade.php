<div>
    <x-ui.page-header
        :title="__('admin.payouts.title')"
        :subtitle="__('admin.payouts.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <div class="mb-7 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.stat-card
            :label="__('admin.payouts.pending_amount')"
            :value="'$'.number_format($stats['pending'], 2)"
            color="yellow"
        />
        <x-ui.stat-card
            :label="__('admin.payouts.completed_month')"
            :value="'$'.number_format($stats['completed_month'], 2)"
            color="green"
        />
    </div>

    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] table-fixed border-collapse text-start">
                <colgroup>
                    <col class="w-[22%]">
                    <col class="w-[14%]">
                    <col class="w-[16%]">
                    <col class="w-[14%]">
                    <col class="w-[14%]">
                    <col class="w-[20%]">
                </colgroup>
                <thead>
                    <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.payouts.table_partner') }}</th>
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.payouts.table_amount') }}</th>
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.payouts.table_method') }}</th>
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.payouts.table_date') }}</th>
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.status') }}</th>
                        <th class="pb-3.5 text-start font-medium">{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                    @foreach($payouts as $payout)
                        <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="payout-{{ $payout['id'] }}">
                            <td class="py-3.5 pe-4 align-middle font-semibold">{{ $payout['partner'] }}</td>
                            <td class="py-3.5 pe-4 align-middle font-bold text-brand-green">${{ number_format($payout['amount'], 2) }}</td>
                            <td class="py-3.5 pe-4 align-middle">{{ $payout['method'] }}</td>
                            <td class="py-3.5 pe-4 align-middle text-surface-muted dark:text-brand-muted">{{ $payout['date'] }}</td>
                            <td class="py-3.5 pe-4 align-middle">
                                <x-ui.badge :type="$payout['status'] === 'completed' ? 'completed' : ($payout['status'] === 'approved' ? 'approved' : 'pending')">
                                    @if($payout['status'] === 'pending')
                                        {{ __('ui.status_pending') }}
                                    @elseif($payout['status'] === 'approved')
                                        {{ __('ui.status_approved') }}
                                    @else
                                        {{ __('ui.status_completed') }}
                                    @endif
                                </x-ui.badge>
                            </td>
                            <td class="py-3.5 align-middle">
                                @if($payout['status'] === 'pending')
                                    <x-ui.button variant="success" size="sm" wire:click="processPayout('{{ $payout['id'] }}')">
                                        💳 {{ __('admin.payouts.process') }}
                                    </x-ui.button>
                                @else
                                    <span class="text-xs text-surface-muted dark:text-brand-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
