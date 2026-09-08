<div>
    <x-ui.page-header
        :title="__('admin.api_logs.title')"
        :subtitle="__('admin.api_logs.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <x-ui.card class="mb-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.form-group :label="__('admin.api_logs.filter_type')">
                <x-ui.select wire:model.live="type">
                    <option value="">{{ __('admin.api_logs.filter_all') }}</option>
                    @foreach($types as $value)
                        <option value="{{ $value }}">{{ __('admin.api_logs.types.'.$value) }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.api_logs.filter_service')">
                <x-ui.select wire:model.live="service">
                    <option value="">{{ __('admin.api_logs.filter_all') }}</option>
                    @foreach($services as $value)
                        <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.api_logs.filter_method')">
                <x-ui.select wire:model.live="method">
                    <option value="">{{ __('admin.api_logs.filter_all') }}</option>
                    @foreach($methods as $value)
                        <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.api_logs.filter_status')">
                <x-ui.input wire:model.live.debounce.400ms="status" type="text" inputmode="numeric" placeholder="200" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.api_logs.filter_endpoint')">
                <x-ui.input wire:model.live.debounce.400ms="endpoint" type="text" placeholder="/api/user/esim" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.api_logs.filter_user')">
                <x-ui.input wire:model.live.debounce.400ms="user" type="text" :placeholder="__('admin.api_logs.user_placeholder')" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.api_logs.filter_from')">
                <x-ui.input wire:model.live="dateFrom" type="date" />
            </x-ui.form-group>
            <x-ui.form-group :label="__('admin.api_logs.filter_to')">
                <x-ui.input wire:model.live="dateTo" type="date" />
            </x-ui.form-group>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-surface-text dark:text-brand-text">
                <input type="checkbox" wire:model.live="failed" class="rounded border-surface-border text-brand-cyan focus:ring-brand-cyan">
                {{ __('admin.api_logs.filter_failed') }}
            </label>
            <label class="flex items-center gap-2 text-sm text-surface-text dark:text-brand-text">
                <input type="checkbox" wire:model.live="slow" class="rounded border-surface-border text-brand-cyan focus:ring-brand-cyan">
                {{ __('admin.api_logs.filter_slow') }}
            </label>
            <x-ui.button variant="secondary" size="sm" wire:click="resetFilters">
                {{ __('admin.api_logs.reset') }}
            </x-ui.button>
        </div>
    </x-ui.card>

    <x-ui.card>
        @if($logs->isEmpty())
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('admin.api_logs.empty')"
                icon="📡"
            />
        @else
            <div class="space-y-3 md:hidden">
                @foreach($logs as $log)
                    <a
                        href="{{ route('admin.api-logs.show', $log) }}"
                        class="block rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                        wire:key="log-mobile-{{ $log->id }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-surface-text dark:text-brand-text">{{ $log->method }} {{ $log->endpoint }}</div>
                                <div class="mt-1 text-xs text-surface-muted dark:text-brand-muted">{{ $log->created_at?->format('Y-m-d H:i:s') }}</div>
                            </div>
                            <x-ui.badge :type="$log->isFailed() ? 'rejected' : 'active'">
                                {{ $log->response_status ?? '—' }}
                            </x-ui.badge>
                        </div>
                        <div class="mt-3 text-xs text-surface-muted dark:text-brand-muted">
                            {{ __('admin.api_logs.types.'.$log->type) }} · {{ $log->service }} · {{ $log->response_time_ms }}ms
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[960px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.api_logs.table_time') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.api_logs.table_type') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.api_logs.table_service') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.api_logs.table_method') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.api_logs.table_endpoint') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.api_logs.table_status') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.api_logs.table_time_ms') }}</th>
                            <th class="pb-3.5 pe-3 text-start font-medium">{{ __('admin.api_logs.table_user') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('admin.api_logs.table_reference') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($logs as $log)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="log-{{ $log->id }}">
                                <td class="py-3 pe-3 align-middle whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="py-3 pe-3 align-middle">{{ __('admin.api_logs.types.'.$log->type) }}</td>
                                <td class="py-3 pe-3 align-middle">{{ $log->service }}</td>
                                <td class="py-3 pe-3 align-middle font-semibold">{{ $log->method }}</td>
                                <td class="py-3 pe-3 align-middle">
                                    <a href="{{ route('admin.api-logs.show', $log) }}" class="text-brand-cyan hover:underline">
                                        <span class="block truncate" title="{{ $log->endpoint }}">{{ $log->endpoint }}</span>
                                    </a>
                                </td>
                                <td class="py-3 pe-3 align-middle">
                                    <x-ui.badge :type="$log->isFailed() ? 'rejected' : 'active'">
                                        {{ $log->response_status ?? '—' }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-3 pe-3 align-middle">{{ number_format($log->response_time_ms) }}ms</td>
                                <td class="py-3 pe-3 align-middle">{{ $log->user?->email ?? '—' }}</td>
                                <td class="py-3 align-middle">
                                    @if($log->reference_type && $log->reference_id)
                                        {{ $log->reference_type }}: {{ \Illuminate\Support\Str::limit($log->reference_id, 12) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
