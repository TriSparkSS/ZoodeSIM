<div>
    <x-ui.page-header
        :title="__('admin.transactions.title')"
        :subtitle="__('admin.transactions.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <x-ui.card class="mb-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.form-group :label="__('admin.transactions.filter_id')">
                <x-ui.input wire:model.live.debounce.400ms="transactionId" :placeholder="__('admin.transactions.id_placeholder')" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_user')">
                <x-ui.input wire:model.live.debounce.400ms="user" :placeholder="__('admin.transactions.user_placeholder')" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_partner')">
                <x-ui.input wire:model.live.debounce.400ms="partner" :placeholder="__('admin.transactions.partner_placeholder')" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_type')">
                <x-ui.select wire:model.live="type">
                    <option value="">{{ __('admin.transactions.filter_all') }}</option>
                    @foreach($types as $value)
                        <option value="{{ $value }}">{{ __('admin.transactions.types.'.$value) }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_category')">
                <x-ui.select wire:model.live="category">
                    <option value="">{{ __('admin.transactions.filter_all') }}</option>
                    @foreach($categories as $value)
                        <option value="{{ $value }}">{{ __('admin.partners.wallet.categories.'.$value) }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_status')">
                <x-ui.select wire:model.live="status">
                    <option value="">{{ __('admin.transactions.filter_all') }}</option>
                    @foreach($statuses as $value)
                        <option value="{{ $value }}">{{ __('admin.transactions.statuses.'.$value) }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_from')">
                <x-ui.input wire:model.live="dateFrom" type="date" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_to')">
                <x-ui.input wire:model.live="dateTo" type="date" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_unit')">
                <x-ui.select wire:model.live="currency">
                    <option value="">{{ __('admin.transactions.filter_all') }}</option>
                    <option value="amount">{{ __('admin.transactions.units.amount') }}</option>
                    <option value="mb">{{ __('admin.transactions.units.mb') }}</option>
                </x-ui.select>
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_amount_min')">
                <x-ui.input wire:model.live.debounce.400ms="amountMin" type="text" inputmode="decimal" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_amount_max')">
                <x-ui.input wire:model.live.debounce.400ms="amountMax" type="text" inputmode="decimal" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.transactions.filter_promo')">
                <x-ui.input wire:model.live.debounce.400ms="promo" :placeholder="__('admin.transactions.promo_placeholder')" />
            </x-ui.form-group>
        </div>

        <div class="mt-4">
            <x-ui.button variant="secondary" size="sm" wire:click="resetFilters">
                {{ __('admin.transactions.reset') }}
            </x-ui.button>
        </div>
    </x-ui.card>

    <x-ui.card>
        @if($rows->isEmpty())
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('admin.transactions.empty')"
                icon="📒"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_id') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_owner') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_type') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_category') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('ui.amount') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_before') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_after') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.transactions.table_promo') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('ui.status') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('ui.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($rows as $row)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="txn-{{ $row->id }}">
                                <td class="py-3 pe-3 align-middle font-mono text-xs font-semibold">{{ $row->transaction_id }}</td>
                                <td class="py-3 pe-3 align-middle">
                                    <div class="font-semibold">{{ $row->transactable?->name ?? '—' }}</div>
                                    <div class="text-[11px] text-surface-muted dark:text-brand-muted">
                                        {{ $row->transactable_type === \App\Models\User::class ? __('admin.transactions.owner_user') : __('admin.transactions.owner_partner') }}
                                        @if($row->transactable?->email)
                                            · {{ $row->transactable->email }}
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 pe-3 align-middle">
                                    <span @class([
                                        'font-semibold',
                                        'text-brand-green' => $row->type === 'credit',
                                        'text-brand-red' => $row->type !== 'credit',
                                    ])>
                                        {{ __('admin.transactions.types.'.$row->type) }}
                                    </span>
                                </td>
                                <td class="py-3 pe-3 align-middle">{{ __('admin.partners.wallet.categories.'.$row->category) }}</td>
                                <td class="py-3 pe-3 align-middle font-semibold">
                                    {{ $row->type === 'credit' ? '+' : '−' }}
                                    {{ $row->currency === 'MB' ? number_format((float) $row->amount, 0).' MB' : '$'.number_format((float) $row->amount, 2) }}
                                </td>
                                <td class="py-3 pe-3 align-middle text-surface-muted dark:text-brand-muted">
                                    {{ $row->currency === 'MB' ? number_format((float) $row->balance_before, 0).' MB' : '$'.number_format((float) $row->balance_before, 2) }}
                                </td>
                                <td class="py-3 pe-3 align-middle font-semibold">
                                    {{ $row->currency === 'MB' ? number_format((float) $row->balance_after, 0).' MB' : '$'.number_format((float) $row->balance_after, 2) }}
                                </td>
                                <td class="py-3 pe-3 align-middle text-xs">
                                    {{ $row->promoCode?->code ?? ($row->meta['promo_code'] ?? '—') }}
                                    @if(! empty($row->meta['commission']))
                                        <div class="text-surface-muted dark:text-brand-muted">${{ $row->meta['commission'] }}</div>
                                    @endif
                                </td>
                                <td class="py-3 pe-3 align-middle">
                                    <x-ui.badge :type="$row->status === 'completed' ? 'completed' : ($row->status === 'failed' ? 'rejected' : 'pending')">
                                        {{ __('admin.transactions.statuses.'.$row->status) }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-3 align-middle text-xs text-surface-muted dark:text-brand-muted">
                                    {{ $row->created_at?->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $rows->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
