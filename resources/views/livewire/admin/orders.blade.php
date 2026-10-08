@php
    $sourceTabs = [
        'portal' => __('admin.orders.tab_portal'),
        'live' => __('admin.orders.tab_live'),
    ];
@endphp

<div class="min-w-0">
    <x-ui.page-header
        :title="__('admin.orders.title')"
        :subtitle="__('admin.orders.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <div class="mb-5 flex flex-wrap gap-1.5">
        @foreach($sourceTabs as $key => $label)
            <label @class([
                'cursor-pointer rounded-lg border px-3.5 py-1.5 text-xs font-semibold transition-all duration-200',
                'border-brand-cyan bg-brand-cyan/10 text-brand-cyan' => $source === $key,
                'border-surface-border text-surface-muted hover:text-surface-text dark:border-brand-border dark:text-brand-muted dark:hover:text-brand-text' => $source !== $key,
            ])>
                <input type="radio" wire:model.live="source" value="{{ $key }}" class="sr-only">
                {{ $label }}
            </label>
        @endforeach
    </div>

    <x-ui.card class="mb-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 [&>*]:min-w-0">
            <x-ui.form-group :label="__('admin.orders.filter_client')">
                <x-ui.input wire:model.live.debounce.400ms="clientId" :placeholder="__('admin.orders.client_placeholder')" />
            </x-ui.form-group>
            @if($source !== 'live')
                <x-ui.form-group :label="__('admin.orders.filter_user')">
                    <x-ui.input wire:model.live.debounce.400ms="user" :placeholder="__('admin.orders.user_placeholder')" />
                </x-ui.form-group>
            @endif
            <x-ui.form-group :label="__('admin.orders.filter_status')">
                <x-ui.select wire:model.live="status">
                    <option value="">{{ __('admin.orders.filter_all') }}</option>
                    @foreach($statuses as $value)
                        <option value="{{ $value }}">{{ __('admin.orders.statuses.'.$value) }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>
            @if($source !== 'live')
                <x-ui.form-group :label="__('admin.orders.filter_payment')">
                    <x-ui.select wire:model.live="paymentStatus">
                        <option value="">{{ __('admin.orders.filter_all') }}</option>
                        @foreach($paymentStatuses as $value)
                            <option value="{{ $value }}">{{ __('admin.orders.payment_statuses.'.$value) }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.form-group>
            @endif
            <x-ui.form-group :label="__('admin.orders.filter_package')">
                <x-ui.input wire:model.live.debounce.400ms="packageCode" :placeholder="__('admin.orders.package_placeholder')" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.orders.filter_location')">
                <x-ui.input wire:model.live.debounce.400ms="location" :placeholder="__('admin.orders.location_placeholder')" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.orders.filter_from')">
                <x-ui.input wire:model.live="dateFrom" type="date" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.orders.filter_to')">
                <x-ui.input wire:model.live="dateTo" type="date" />
            </x-ui.form-group>
        </div>

        <div class="mt-4">
            <x-ui.button variant="secondary" size="sm" wire:click="resetFilters">
                {{ __('admin.orders.reset') }}
            </x-ui.button>
        </div>
    </x-ui.card>

    <x-ui.card>
        @if($source === 'live')
            @if(! $liveAvailable)
                <x-ui.empty-state
                    :title="__('ui.no_results')"
                    :description="__('admin.orders.live_unavailable')"
                    icon="📡"
                />
            @elseif(count($liveRows) === 0)
                <x-ui.empty-state
                    :title="__('ui.no_results')"
                    :description="__('admin.orders.live_empty')"
                    icon="📦"
                />
            @else
                <div class="space-y-3 md:hidden">
                    @foreach($liveRows as $row)
                        @php
                            $liveStatus = $row->status ?? '';
                            $statusLabel = $liveStatus !== '' && trans()->has('admin.orders.statuses.'.$liveStatus)
                                ? __('admin.orders.statuses.'.$liveStatus)
                                : ($liveStatus !== '' ? $liveStatus : '—');
                        @endphp
                        <div
                            class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                            wire:key="live-order-mobile-{{ $row->id }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-surface-text dark:text-brand-text">{{ $row->packageName ?: '—' }}</div>
                                    <div class="truncate text-xs text-surface-muted dark:text-brand-muted">{{ $row->packageCode ?: '—' }}</div>
                                </div>
                                <x-ui.badge :type="$liveStatus === 'active' ? 'active' : ($liveStatus === 'failed' ? 'rejected' : 'pending')">
                                    {{ $statusLabel }}
                                </x-ui.badge>
                            </div>
                            <div class="mt-3 text-sm font-semibold text-surface-text dark:text-brand-text">
                                @if($row->amount !== null)
                                    ${{ number_format((float) $row->amount, 2) }}
                                @else
                                    —
                                @endif
                            </div>
                            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-surface-muted dark:text-brand-muted">
                                <span class="break-all font-mono">{{ $row->id }}</span>
                                <span>{{ $row->clientId ?? '—' }}</span>
                                <span>{{ $row->location ?: '—' }}</span>
                                <span>{{ $row->createdAt ?: '—' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[960px] border-collapse text-start">
                        <thead>
                            <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                                <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_id') }}</th>
                                <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_client') }}</th>
                                <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_package') }}</th>
                                <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_location') }}</th>
                                <th class="pb-3.5 pe-3 text-start font-medium">{{ __('ui.amount') }}</th>
                                <th class="pb-3.5 pe-3 text-start font-medium">{{ __('ui.status') }}</th>
                                <th class="pb-3.5 text-start font-medium">{{ __('ui.date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                            @foreach($liveRows as $row)
                                @php
                                    $liveStatus = $row->status ?? '';
                                    $statusLabel = $liveStatus !== '' && trans()->has('admin.orders.statuses.'.$liveStatus)
                                        ? __('admin.orders.statuses.'.$liveStatus)
                                        : ($liveStatus !== '' ? $liveStatus : '—');
                                @endphp
                                <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="live-order-{{ $row->id }}">
                                    <td class="py-3 pe-3 align-middle font-mono text-xs font-semibold whitespace-nowrap">{{ $row->id }}</td>
                                    <td class="py-3 pe-3 align-middle font-mono text-xs whitespace-nowrap">{{ $row->clientId ?? '—' }}</td>
                                    <td class="py-3 pe-3 align-middle">
                                        <div class="font-semibold">{{ $row->packageName ?: '—' }}</div>
                                        <div class="text-[11px] text-surface-muted dark:text-brand-muted">{{ $row->packageCode ?: '—' }}</div>
                                    </td>
                                    <td class="py-3 pe-3 align-middle whitespace-nowrap">{{ $row->location ?: '—' }}</td>
                                    <td class="py-3 pe-3 align-middle font-semibold whitespace-nowrap">
                                        @if($row->amount !== null)
                                            ${{ number_format((float) $row->amount, 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="py-3 pe-3 align-middle">
                                        <x-ui.badge :type="$liveStatus === 'active' ? 'active' : ($liveStatus === 'failed' ? 'rejected' : 'pending')">
                                            {{ $statusLabel }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="py-3 align-middle text-xs whitespace-nowrap text-surface-muted dark:text-brand-muted">
                                        {{ $row->createdAt ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @elseif($rows->isEmpty())
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('admin.orders.empty')"
                icon="📦"
            />
        @else
            <div class="space-y-3 md:hidden">
                @foreach($rows as $row)
                    <div
                        class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                        wire:key="esim-order-mobile-{{ $row->id }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-surface-text dark:text-brand-text">{{ $row->package_name }}</div>
                                <div class="truncate text-xs text-surface-muted dark:text-brand-muted">{{ $row->package_code }}</div>
                                <div class="mt-1 truncate text-xs text-surface-text dark:text-brand-text">{{ $row->user?->name ?? '—' }}</div>
                                @if($row->user?->email)
                                    <div class="truncate text-xs text-surface-muted dark:text-brand-muted">{{ $row->user->email }}</div>
                                @endif
                            </div>
                            <x-ui.badge :type="$row->order_status === 'active' ? 'active' : ($row->order_status === 'failed' ? 'rejected' : 'pending')">
                                {{ __('admin.orders.statuses.'.$row->order_status) }}
                            </x-ui.badge>
                        </div>
                        <dl class="mt-4 grid grid-cols-3 gap-2">
                            <div class="min-w-0">
                                <dt class="text-[10px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.orders.table_actual_price') }}</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-surface-text dark:text-brand-text">${{ number_format((float) $row->provider_cost, 2) }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-[10px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.orders.table_user_paid') }}</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-surface-text dark:text-brand-text">${{ number_format((float) ($row->charged_amount ?? $row->customer_price), 2) }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-[10px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.orders.table_profit') }}</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-brand-green">${{ number_format((float) $row->adminProfit(), 2) }}</dd>
                            </div>
                        </dl>
                        <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-surface-muted dark:text-brand-muted">
                            <span class="break-all font-mono">{{ $row->id }}</span>
                            <span>{{ $row->resellportal_client_id ?? '—' }}</span>
                            <span>{{ $row->package_location ?: '—' }}</span>
                            <span>{{ $row->created_at?->format('Y-m-d H:i') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[1100px] border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_id') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_user') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_client') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_package') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_location') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_actual_price') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_user_paid') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.orders.table_profit') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('ui.status') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('ui.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($rows as $row)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="esim-order-{{ $row->id }}">
                                <td class="py-3 pe-3 align-middle font-mono text-xs font-semibold whitespace-nowrap">{{ $row->id }}</td>
                                <td class="max-w-[12rem] py-3 pe-3 align-middle">
                                    <div class="truncate font-semibold">{{ $row->user?->name ?? '—' }}</div>
                                    <div class="truncate text-[11px] text-surface-muted dark:text-brand-muted">
                                        @if($row->user?->email)
                                            {{ $row->user->email }}
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 pe-3 align-middle font-mono text-xs whitespace-nowrap">{{ $row->resellportal_client_id ?? '—' }}</td>
                                <td class="max-w-[12rem] py-3 pe-3 align-middle">
                                    <div class="truncate font-semibold">{{ $row->package_name }}</div>
                                    <div class="truncate text-[11px] text-surface-muted dark:text-brand-muted">{{ $row->package_code }}</div>
                                </td>
                                <td class="py-3 pe-3 align-middle whitespace-nowrap">{{ $row->package_location ?: '—' }}</td>
                                <td class="py-3 pe-3 align-middle font-semibold whitespace-nowrap">${{ number_format((float) $row->provider_cost, 2) }}</td>
                                <td class="py-3 pe-3 align-middle font-semibold whitespace-nowrap">${{ number_format((float) ($row->charged_amount ?? $row->customer_price), 2) }}</td>
                                <td class="py-3 pe-3 align-middle font-semibold whitespace-nowrap text-brand-green">${{ number_format((float) $row->adminProfit(), 2) }}</td>
                                <td class="py-3 pe-3 align-middle">
                                    <x-ui.badge :type="$row->order_status === 'active' ? 'active' : ($row->order_status === 'failed' ? 'rejected' : 'pending')">
                                        {{ __('admin.orders.statuses.'.$row->order_status) }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-3 align-middle text-xs whitespace-nowrap text-surface-muted dark:text-brand-muted">
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
