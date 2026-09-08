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
        @if(count($payouts) === 0)
            <x-ui.empty-state :title="__('ui.no_data')" :description="__('admin.payouts.empty')" />
        @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] table-fixed border-collapse text-start">
                <colgroup>
                    <col class="w-[18%]">
                    <col class="w-[12%]">
                    <col class="w-[14%]">
                    <col class="w-[16%]">
                    <col class="w-[12%]">
                    <col class="w-[12%]">
                    <col class="w-[16%]">
                </colgroup>
                <thead>
                    <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.payouts.table_partner') }}</th>
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.payouts.table_amount') }}</th>
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.payouts.table_method') }}</th>
                        <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.payouts.table_details') }}</th>
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
                            <td class="py-3.5 pe-4 align-middle">
                                {{ \App\Models\Withdrawal::methodLabel((string) $payout['method']) }}
                            </td>
                            <td class="py-3.5 pe-4 align-middle text-surface-muted dark:text-brand-muted">{{ $payout['details'] ?: '—' }}</td>
                            <td class="py-3.5 pe-4 align-middle text-surface-muted dark:text-brand-muted">{{ $payout['date'] }}</td>
                            <td class="py-3.5 pe-4 align-middle">
                                @php
                                    $statusLabel = match ($payout['status']) {
                                        'completed' => __('ui.status_completed'),
                                        'rejected' => __('ui.status_rejected'),
                                        'failed' => __('ui.status_failed'),
                                        'processing' => __('ui.status_processing'),
                                        default => __('ui.status_pending'),
                                    };
                                @endphp
                                <x-ui.badge :type="$payout['badge']">{{ $statusLabel }}</x-ui.badge>
                            </td>
                            <td class="py-3.5 align-middle">
                                @if($payout['is_pending'])
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.button
                                            variant="success"
                                            size="sm"
                                            wire:click="completePayout('{{ $payout['id'] }}')"
                                            wire:confirm="{{ __('admin.payouts.complete_confirm') }}"
                                        >
                                            {{ __('admin.payouts.process') }}
                                        </x-ui.button>
                                        <x-ui.button
                                            variant="danger"
                                            size="sm"
                                            wire:click="openRejectModal('{{ $payout['id'] }}')"
                                        >
                                            {{ __('admin.payouts.reject') }}
                                        </x-ui.button>
                                    </div>
                                @else
                                    <span class="text-xs text-surface-muted dark:text-brand-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </x-ui.card>

    <x-ui.modal wire:model="showRejectModal" :title="__('admin.payouts.reject')">
        <div class="space-y-5 text-sm">
            <x-ui.form-group :label="__('admin.payouts.note')">
                <x-ui.input wire:model="rejectNote" :placeholder="__('admin.payouts.note_placeholder')" />
                @error('rejectNote')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <div class="flex justify-end gap-2">
                <x-ui.button variant="secondary" wire:click="closeRejectModal">{{ __('ui.cancel') }}</x-ui.button>
                <x-ui.button
                    variant="danger"
                    wire:click="rejectPayout"
                    wire:confirm="{{ __('admin.payouts.reject_confirm') }}"
                >
                    {{ __('admin.payouts.reject') }}
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
