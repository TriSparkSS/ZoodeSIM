<div>
    <x-ui.page-header
        :title="__('admin.promo_audit.title')"
        :subtitle="__('admin.promo_audit.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <x-ui.card class="mb-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end">
            <x-ui.search-input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('admin.promo_audit.search_placeholder')"
                class="w-full max-w-sm"
            />
            <x-ui.form-group :label="__('admin.promo_audit.filter_action')" class="md:w-56">
                <x-ui.select wire:model.live="action">
                    <option value="">{{ __('admin.promo_audit.filter_all') }}</option>
                    @foreach($actions as $value)
                        <option value="{{ $value }}">{{ __('admin.promo_audit.actions.'.$value) }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-group>
        </div>
    </x-ui.card>

    <x-ui.card>
        @if($logs->isEmpty())
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('admin.promo_audit.empty')"
                icon="🎟️"
            />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.date') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.promo_audit.table_action') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.promo_codes.table_code') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.promo_codes.table_partner') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('admin.promo_audit.table_ip') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($logs as $log)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="promo-audit-{{ $log->id }}">
                                <td class="py-3.5 pe-4 align-middle text-surface-muted dark:text-brand-muted">
                                    {{ $log->created_at?->format('Y-m-d H:i') }}
                                </td>
                                <td class="py-3.5 pe-4 align-middle font-semibold">
                                    {{ __('admin.promo_audit.actions.'.$log->action) }}
                                </td>
                                <td class="py-3.5 pe-4 align-middle tracking-widest text-brand-cyan">
                                    {{ $log->code ?? $log->promoCode?->code ?? '—' }}
                                </td>
                                <td class="py-3.5 pe-4 align-middle">
                                    {{ $log->partner?->name ?? '—' }}
                                </td>
                                <td class="py-3.5 align-middle text-xs text-surface-muted dark:text-brand-muted">
                                    {{ $log->ip_address ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $logs->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
