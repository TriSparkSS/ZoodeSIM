<div>
    <x-ui.page-header
        :title="__('admin.banners.title')"
        :subtitle="__('admin.banners.subtitle')"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <x-ui.button wire:click="openCreateModal">
                {{ __('admin.banners.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <div class="mb-4 flex flex-col gap-3">
            <div class="flex flex-wrap gap-2">
                @foreach(['all', 'active', 'inactive'] as $filter)
                    <x-ui.button
                        size="sm"
                        :variant="$statusFilter === $filter ? 'primary' : 'secondary'"
                        wire:click="$set('statusFilter', '{{ $filter }}')"
                    >
                        {{ __('admin.banners.filter_'.$filter) }}
                    </x-ui.button>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach(['all', 'user_app', 'partner_panel', 'both'] as $filter)
                    <x-ui.button
                        size="sm"
                        :variant="$displayFilter === $filter ? 'primary' : 'secondary'"
                        wire:click="$set('displayFilter', '{{ $filter }}')"
                    >
                        {{ $filter === 'all' ? __('admin.banners.filter_all_displays') : __('admin.banners.display_'.$filter) }}
                    </x-ui.button>
                @endforeach
            </div>
        </div>

        @if($banners === [])
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('admin.banners.empty')"
                icon="🖼️"
            />
        @else
            <div class="space-y-3 md:hidden">
                @foreach($banners as $banner)
                    <div
                        class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                        wire:key="banner-mobile-{{ $banner['id'] }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                @if($banner['image_url'])
                                    <img src="{{ $banner['image_url'] }}" alt="" class="h-12 w-20 rounded object-cover">
                                @endif
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-surface-text dark:text-brand-text">{{ $banner['title'] }}</div>
                                    <div class="mt-1 text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.banners.display_'.$banner['display_on']) }}</div>
                                </div>
                            </div>
                            <x-ui.badge :type="$banner['is_active'] ? 'active' : 'inactive'">
                                {{ $banner['is_active'] ? __('ui.status_active') : __('admin.banners.status_inactive') }}
                            </x-ui.badge>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-ui.button size="sm" variant="outline" wire:click="openEditModal('{{ $banner['id'] }}')">
                                {{ __('admin.banners.edit') }}
                            </x-ui.button>
                            <x-ui.button size="sm" variant="secondary" wire:click="toggle('{{ $banner['id'] }}')">
                                {{ $banner['is_active'] ? __('admin.banners.deactivate') : __('admin.banners.activate') }}
                            </x-ui.button>
                            <x-ui.button size="sm" variant="secondary" wire:click="delete('{{ $banner['id'] }}')" wire:confirm="{{ __('admin.banners.delete_confirm') }}">
                                {{ __('admin.banners.delete') }}
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[880px] table-fixed border-collapse text-start">
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.banners.table_image') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.banners.table_title') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.banners.table_display') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.banners.table_sort') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.status') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('admin.banners.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($banners as $banner)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="banner-{{ $banner['id'] }}">
                                <td class="py-3.5 pe-4 align-middle">
                                    @if($banner['image_url'])
                                        <img src="{{ $banner['image_url'] }}" alt="" class="h-10 w-16 rounded object-cover">
                                    @else
                                        <span class="text-surface-muted dark:text-brand-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-3.5 pe-4 align-middle font-semibold">{{ $banner['title'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ __('admin.banners.display_'.$banner['display_on']) }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $banner['sort_order'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <x-ui.badge :type="$banner['is_active'] ? 'active' : 'inactive'">
                                        {{ $banner['is_active'] ? __('ui.status_active') : __('admin.banners.status_inactive') }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-3.5 align-middle">
                                    <div class="flex flex-wrap gap-2">
                                        <x-ui.button size="sm" variant="outline" wire:click="openEditModal('{{ $banner['id'] }}')">
                                            {{ __('admin.banners.edit') }}
                                        </x-ui.button>
                                        <x-ui.button size="sm" variant="secondary" wire:click="toggle('{{ $banner['id'] }}')">
                                            {{ $banner['is_active'] ? __('admin.banners.deactivate') : __('admin.banners.activate') }}
                                        </x-ui.button>
                                        <x-ui.button size="sm" variant="secondary" wire:click="delete('{{ $banner['id'] }}')" wire:confirm="{{ __('admin.banners.delete_confirm') }}">
                                            {{ __('admin.banners.delete') }}
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

    <x-ui.modal wire:model="showFormModal" :title="$editingId ? __('admin.banners.edit_modal_title') : __('admin.banners.create_modal_title')" maxWidth="max-w-xl">
        <div class="space-y-5 text-sm">
            <x-ui.form-group :label="__('admin.banners.form_title')" required>
                <x-ui.input wire:model="formTitle" />
                @error('formTitle')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <x-ui.form-group :label="__('admin.banners.form_display_on')" required>
                <div class="flex flex-wrap gap-2">
                    @foreach(\App\Models\Banner::displayTargets() as $value)
                        <button
                            type="button"
                            wire:click="$set('formDisplayOn', '{{ $value }}')"
                            @class([
                                'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                'bg-brand-cyan/15 text-brand-cyan' => $formDisplayOn === $value,
                                'bg-surface-card-alt text-surface-muted hover:text-surface-text dark:bg-brand-card-alt dark:text-brand-muted dark:hover:text-brand-text' => $formDisplayOn !== $value,
                            ])
                        >
                            {{ __('admin.banners.display_'.$value) }}
                        </button>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.banners.display_help') }}</p>
                @error('formDisplayOn')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <x-ui.form-group :label="__('admin.banners.form_link')">
                <x-ui.input wire:model="formLinkUrl" type="url" placeholder="https://" />
                @error('formLinkUrl')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <x-ui.form-group :label="__('admin.banners.form_sort')" required>
                <x-ui.input wire:model="formSortOrder" type="number" min="0" />
                @error('formSortOrder')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <x-ui.form-group :label="__('admin.banners.form_image')" :required="! $editingId">
                <input
                    type="file"
                    wire:model="formImage"
                    accept="image/jpeg,image/png,image/webp"
                    class="w-full rounded-xl border border-surface-border bg-surface-card-alt px-4 py-3 text-sm text-surface-text dark:border-brand-border dark:bg-brand-card-alt dark:text-brand-text"
                >
                @error('formImage')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.banners.image_help') }}</p>
                @if($editingImageUrl)
                    <img src="{{ $editingImageUrl }}" alt="" class="mt-3 h-20 w-full max-w-xs rounded object-cover">
                @endif
            </x-ui.form-group>

            <label class="flex items-center gap-2 text-sm text-surface-text dark:text-brand-text">
                <input type="checkbox" wire:model="formIsActive" class="rounded border-surface-border text-brand-cyan focus:ring-brand-cyan">
                {{ __('admin.banners.form_active') }}
            </label>

            <div class="flex justify-end gap-2">
                <x-ui.button variant="secondary" wire:click="closeModal">{{ __('ui.cancel') }}</x-ui.button>
                <x-ui.button wire:click="save">{{ $editingId ? __('admin.banners.save') : __('admin.banners.create') }}</x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
