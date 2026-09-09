<div>
    <x-ui.page-header
        :title="__('admin.countries.title')"
        :subtitle="__('admin.countries.subtitle')"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <x-ui.button wire:click="openCreateModal">
                {{ __('admin.countries.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach(['all', 'active', 'inactive'] as $filter)
                <x-ui.button
                    size="sm"
                    :variant="$statusFilter === $filter ? 'primary' : 'secondary'"
                    wire:click="$set('statusFilter', '{{ $filter }}')"
                >
                    {{ __('admin.countries.filter_'.$filter) }}
                </x-ui.button>
            @endforeach
        </div>

        @if($countries === [])
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('admin.countries.empty')"
                icon="🌍"
            />
        @else
            <div class="space-y-3 md:hidden">
                @foreach($countries as $country)
                    <div
                        class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                        wire:key="country-mobile-{{ $country['id'] }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                @if($country['image_url'])
                                    <img src="{{ $country['image_url'] }}" alt="" class="h-8 w-10 rounded object-cover">
                                @endif
                                <div>
                                    <div class="font-semibold text-surface-text dark:text-brand-text">{{ $country['name'] }}</div>
                                    <div class="mt-1 text-sm text-brand-cyan">{{ $country['code'] }}</div>
                                </div>
                            </div>
                            <x-ui.badge :type="$country['is_active'] ? 'active' : 'inactive'">
                                {{ $country['is_active'] ? __('ui.status_active') : __('admin.countries.status_inactive') }}
                            </x-ui.badge>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-ui.button size="sm" variant="outline" wire:click="openEditModal('{{ $country['id'] }}')">
                                {{ __('admin.countries.edit') }}
                            </x-ui.button>
                            <x-ui.button size="sm" variant="secondary" wire:click="toggle('{{ $country['id'] }}')">
                                {{ $country['is_active'] ? __('admin.countries.deactivate') : __('admin.countries.activate') }}
                            </x-ui.button>
                            <x-ui.button size="sm" variant="secondary" wire:click="delete('{{ $country['id'] }}')" wire:confirm="{{ __('admin.countries.delete_confirm') }}">
                                {{ __('admin.countries.delete') }}
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[720px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.countries.table_image') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.countries.table_code') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.countries.table_name') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.countries.table_sort') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.status') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('admin.countries.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($countries as $country)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="country-{{ $country['id'] }}">
                                <td class="py-3.5 pe-4 align-middle">
                                    @if($country['image_url'])
                                        <img src="{{ $country['image_url'] }}" alt="" class="h-8 w-10 rounded object-cover">
                                    @else
                                        <span class="text-surface-muted dark:text-brand-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-3.5 pe-4 align-middle font-semibold">{{ $country['code'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $country['name'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $country['sort_order'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <x-ui.badge :type="$country['is_active'] ? 'active' : 'inactive'">
                                        {{ $country['is_active'] ? __('ui.status_active') : __('admin.countries.status_inactive') }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-3.5 align-middle">
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.button size="sm" variant="outline" wire:click="openEditModal('{{ $country['id'] }}')">
                                            {{ __('admin.countries.edit') }}
                                        </x-ui.button>
                                        <x-ui.button size="sm" variant="secondary" wire:click="toggle('{{ $country['id'] }}')">
                                            {{ $country['is_active'] ? __('admin.countries.deactivate') : __('admin.countries.activate') }}
                                        </x-ui.button>
                                        <x-ui.button size="sm" variant="secondary" wire:click="delete('{{ $country['id'] }}')" wire:confirm="{{ __('admin.countries.delete_confirm') }}">
                                            {{ __('admin.countries.delete') }}
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

    <x-ui.modal wire:model="showFormModal" :title="$editingId ? __('admin.countries.edit_modal_title') : __('admin.countries.create_modal_title')" maxWidth="max-w-xl">
        <div class="space-y-5 text-sm">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('admin.countries.form_code')" required>
                    <x-ui.input wire:model="formCode" maxlength="2" class="uppercase" />
                    @error('formCode')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
                <x-ui.form-group :label="__('admin.countries.form_sort')" required>
                    <x-ui.input wire:model="formSortOrder" type="number" min="0" />
                    @error('formSortOrder')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
            </div>

            <x-ui.form-group :label="__('admin.countries.form_name')" required>
                <x-ui.input wire:model="formName" />
                @error('formName')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <x-ui.form-group :label="__('admin.countries.form_image')">
                <input
                    type="file"
                    wire:model="formImage"
                    accept="image/jpeg,image/png,image/webp,image/svg+xml"
                    class="w-full rounded-xl border border-surface-border bg-surface-card-alt px-4 py-3 text-sm text-surface-text dark:border-brand-border dark:bg-brand-card-alt dark:text-brand-text"
                >
                @error('formImage')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.countries.image_help') }}</p>
                @if($editingImageUrl)
                    <img src="{{ $editingImageUrl }}" alt="" class="mt-3 h-10 w-14 rounded object-cover">
                @endif
            </x-ui.form-group>

            <label class="flex items-center gap-2 text-sm text-surface-text dark:text-brand-text">
                <input type="checkbox" wire:model="formIsActive" class="rounded border-surface-border text-brand-cyan focus:ring-brand-cyan">
                {{ __('admin.countries.form_active') }}
            </label>

            <div class="flex justify-end gap-2">
                <x-ui.button variant="secondary" wire:click="closeModal">{{ __('ui.cancel') }}</x-ui.button>
                <x-ui.button wire:click="save">{{ $editingId ? __('admin.countries.save') : __('admin.countries.create') }}</x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
