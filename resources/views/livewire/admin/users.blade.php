<div>
    <x-ui.page-header
        :title="__('admin.users.title')"
        :subtitle="__('admin.users.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <x-ui.card>
        <div class="mb-5">
            <div class="relative w-full max-w-sm">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('admin.users.search_placeholder')"
                />
                <div wire:loading wire:target="search" class="absolute end-3 top-1/2 -translate-y-1/2">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-brand-cyan border-t-transparent"></span>
                </div>
            </div>
        </div>

        @if(count($users) === 0)
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('admin.users.empty')"
                icon="👤"
            />
        @else
            <div class="space-y-3 md:hidden">
                @foreach($users as $user)
                    <div
                        class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                        wire:key="user-mobile-{{ $user['id'] }}"
                    >
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-surface-text dark:text-brand-text">{{ $user['name'] }}</div>
                            <div class="truncate text-xs text-surface-muted dark:text-brand-muted">{{ $user['email'] }}</div>
                            <div class="mt-1 text-xs text-surface-muted dark:text-brand-muted">{{ $user['phone'] }}</div>
                        </div>
                        <div class="mt-4">
                            <x-ui.button variant="secondary" size="sm" class="w-full" wire:click="openEdit('{{ $user['id'] }}')">
                                {{ __('ui.edit') }}
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[760px] table-fixed border-collapse text-start">
                    <colgroup>
                        <col class="w-[22%]">
                        <col class="w-[24%]">
                        <col class="w-[18%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                    </colgroup>
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.users.table_name') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.users.table_email') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.users.table_phone') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.users.table_bonus') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.users.table_created') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($users as $user)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="user-{{ $user['id'] }}">
                                <td class="py-3.5 pe-4 align-middle font-semibold">{{ $user['name'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $user['email'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $user['phone'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ number_format($user['bonus_mb']) }} MB</td>
                                <td class="py-3.5 pe-4 align-middle text-surface-muted dark:text-brand-muted">{{ $user['created_at'] }}</td>
                                <td class="py-3.5 align-middle">
                                    <x-ui.button variant="secondary" size="sm" wire:click="openEdit('{{ $user['id'] }}')">
                                        {{ __('ui.edit') }}
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <x-ui.modal wire:model="showEditModal" :title="__('admin.users.edit_title')" maxWidth="max-w-xl">
        <div class="space-y-5 text-sm">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('admin.users.table_name')" required>
                    <x-ui.input wire:model="editName" />
                    @error('editName')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
                <x-ui.form-group :label="__('admin.users.table_email')" required>
                    <x-ui.input wire:model="editEmail" type="email" />
                    @error('editEmail')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
            </div>

            <x-ui.form-group :label="__('admin.users.table_phone')" required>
                <x-ui.input wire:model="editPhone" type="tel" />
                @error('editPhone')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('admin.users.new_password')">
                    <x-ui.input wire:model="editPassword" type="password" autocomplete="new-password" />
                    @error('editPassword')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
                <x-ui.form-group :label="__('admin.users.confirm_password')">
                    <x-ui.input wire:model="editPasswordConfirmation" type="password" autocomplete="new-password" />
                </x-ui.form-group>
            </div>

            <p class="text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.users.password_hint') }}</p>

            <div class="flex items-center justify-between border-t border-surface-border pt-3 dark:border-brand-border/40">
                <span class="text-surface-muted dark:text-brand-muted">{{ __('admin.users.table_bonus') }}</span>
                <span class="font-semibold text-surface-text dark:text-brand-text">{{ number_format($editBonusMb) }} MB</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-surface-muted dark:text-brand-muted">{{ __('admin.users.table_created') }}</span>
                <span class="font-semibold text-surface-text dark:text-brand-text">{{ $editCreatedAt ?? '—' }}</span>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button variant="secondary" class="flex-1" wire:click="closeEdit">
                    {{ __('ui.cancel') }}
                </x-ui.button>
                <x-ui.button variant="success" class="flex-1" wire:click="saveEdit">
                    {{ __('ui.save') }}
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
