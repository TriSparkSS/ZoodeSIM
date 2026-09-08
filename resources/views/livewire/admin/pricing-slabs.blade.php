<div>
    <x-ui.page-header
        :title="__('admin.pricing_slabs.title')"
        :subtitle="__('admin.pricing_slabs.subtitle')"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <x-ui.button wire:click="openCreateModal">
                {{ __('admin.pricing_slabs.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="mb-6">
        <h2 class="text-sm font-semibold text-surface-text dark:text-brand-text">{{ __('admin.pricing_slabs.preview_title') }}</h2>
        <p class="mt-1 text-sm text-surface-muted dark:text-brand-muted">{{ __('admin.pricing_slabs.preview_help') }}</p>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <x-ui.form-group :label="__('admin.pricing_slabs.preview_cost')" class="sm:max-w-xs" required>
                <x-ui.input wire:model="previewCost" type="text" inputmode="decimal" />
                @error('previewCost')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>
            <x-ui.button variant="secondary" wire:click="preview">
                {{ __('admin.pricing_slabs.preview') }}
            </x-ui.button>
        </div>

        @if($previewError)
            <p class="mt-4 text-sm text-brand-red">{{ $previewError }}</p>
        @endif

        @if($previewResult)
            <div class="mt-4 grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.pricing_slabs.preview_provider') }}</div>
                    <div class="mt-1 font-semibold text-surface-text dark:text-brand-text">{{ $previewResult['provider_cost'] }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.pricing_slabs.preview_slab') }}</div>
                    <div class="mt-1 font-semibold text-surface-text dark:text-brand-text">{{ $previewResult['markup_percentage'] }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.pricing_slabs.preview_markup') }}</div>
                    <div class="mt-1 font-semibold text-surface-text dark:text-brand-text">{{ $previewResult['markup_amount'] }}</div>
                </div>
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.pricing_slabs.preview_customer') }}</div>
                    <div class="mt-1 font-semibold text-brand-cyan">{{ $previewResult['customer_price'] }}</div>
                </div>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card>
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach([
                'all' => __('admin.pricing_slabs.filter_all'),
                'active' => __('admin.pricing_slabs.filter_active'),
                'inactive' => __('admin.pricing_slabs.filter_inactive'),
            ] as $value => $label)
                <button
                    type="button"
                    wire:click="$set('statusFilter', '{{ $value }}')"
                    @class([
                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                        'bg-brand-cyan/15 text-brand-cyan' => $statusFilter === $value,
                        'bg-surface-card-alt text-surface-muted hover:text-surface-text dark:bg-brand-card-alt dark:text-brand-muted dark:hover:text-brand-text' => $statusFilter !== $value,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if(count($slabs) === 0)
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('admin.pricing_slabs.empty')"
                icon="💲"
            />
        @else
            <div class="space-y-3 md:hidden">
                @foreach($slabs as $slab)
                    <div
                        class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                        wire:key="slab-mobile-{{ $slab['id'] }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold text-surface-text dark:text-brand-text">{{ $slab['min'] }} – {{ $slab['max'] }}</div>
                                <div class="mt-1 text-sm text-brand-cyan">{{ $slab['percentage'] }}</div>
                            </div>
                            <x-ui.badge :type="$slab['is_active'] ? 'active' : 'inactive'">
                                {{ $slab['is_active'] ? __('ui.status_active') : __('admin.pricing_slabs.status_inactive') }}
                            </x-ui.badge>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-ui.button size="sm" variant="outline" wire:click="openEditModal('{{ $slab['id'] }}')">
                                {{ __('admin.pricing_slabs.edit') }}
                            </x-ui.button>
                            <x-ui.button size="sm" variant="secondary" wire:click="toggle('{{ $slab['id'] }}')">
                                {{ $slab['is_active'] ? __('admin.pricing_slabs.deactivate') : __('admin.pricing_slabs.activate') }}
                            </x-ui.button>
                            <x-ui.button size="sm" variant="secondary" wire:click="delete('{{ $slab['id'] }}')" wire:confirm="{{ __('admin.pricing_slabs.delete_confirm') }}">
                                {{ __('admin.pricing_slabs.delete') }}
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[720px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.pricing_slabs.table_min') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.pricing_slabs.table_max') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.pricing_slabs.table_markup') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.pricing_slabs.table_priority') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.status') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('admin.pricing_slabs.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($slabs as $slab)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="slab-{{ $slab['id'] }}">
                                <td class="py-3.5 pe-4 align-middle font-semibold">{{ $slab['min'] }}</td>
                                <td class="py-3.5 pe-4 align-middle font-semibold">{{ $slab['max'] }}</td>
                                <td class="py-3.5 pe-4 align-middle text-brand-cyan">{{ $slab['percentage'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $slab['priority'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <x-ui.badge :type="$slab['is_active'] ? 'active' : 'inactive'">
                                        {{ $slab['is_active'] ? __('ui.status_active') : __('admin.pricing_slabs.status_inactive') }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-3.5 align-middle">
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.button size="sm" variant="outline" wire:click="openEditModal('{{ $slab['id'] }}')">
                                            {{ __('admin.pricing_slabs.edit') }}
                                        </x-ui.button>
                                        <x-ui.button size="sm" variant="secondary" wire:click="toggle('{{ $slab['id'] }}')">
                                            {{ $slab['is_active'] ? __('admin.pricing_slabs.deactivate') : __('admin.pricing_slabs.activate') }}
                                        </x-ui.button>
                                        <x-ui.button size="sm" variant="secondary" wire:click="delete('{{ $slab['id'] }}')" wire:confirm="{{ __('admin.pricing_slabs.delete_confirm') }}">
                                            {{ __('admin.pricing_slabs.delete') }}
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <x-ui.modal wire:model="showFormModal" :title="$editingId ? __('admin.pricing_slabs.edit_modal_title') : __('admin.pricing_slabs.create_modal_title')" maxWidth="max-w-xl">
        <div class="space-y-5 text-sm">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('admin.pricing_slabs.form_min')" required>
                    <x-ui.input wire:model="formMinAmount" type="text" inputmode="decimal" />
                    @error('formMinAmount')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
                <x-ui.form-group :label="__('admin.pricing_slabs.form_max')" required>
                    <x-ui.input wire:model="formMaxAmount" type="text" inputmode="decimal" />
                    @error('formMaxAmount')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('admin.pricing_slabs.form_percentage')" required>
                    <x-ui.input wire:model="formPercentage" type="text" inputmode="decimal" />
                    @error('formPercentage')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
                <x-ui.form-group :label="__('admin.pricing_slabs.form_priority')" required>
                    <x-ui.input wire:model="formPriority" type="number" min="0" />
                    @error('formPriority')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
            </div>

            <label class="flex items-center gap-2 text-sm text-surface-text dark:text-brand-text">
                <input type="checkbox" wire:model="formIsActive" class="rounded border-surface-border text-brand-cyan focus:ring-brand-cyan">
                {{ __('admin.pricing_slabs.form_active') }}
            </label>

            <p class="text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.pricing_slabs.range_help') }}</p>

            <div class="flex justify-end gap-2">
                <x-ui.button variant="secondary" wire:click="closeModal">{{ __('ui.cancel') }}</x-ui.button>
                <x-ui.button wire:click="save">{{ $editingId ? __('admin.pricing_slabs.save') : __('admin.pricing_slabs.create') }}</x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
