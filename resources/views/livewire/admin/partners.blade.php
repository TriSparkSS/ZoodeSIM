<div>
    <x-ui.page-header
        :title="__('admin.partners.title')"
        :subtitle="__('admin.partners.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <x-ui.card>
        <div class="mb-5">
            <div class="relative w-full max-w-sm">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('admin.partners.search_placeholder')"
                />
                <div wire:loading wire:target="search" class="absolute end-3 top-1/2 -translate-y-1/2">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-brand-cyan border-t-transparent"></span>
                </div>
            </div>
        </div>

        @if(count($partners) === 0)
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('ui.empty_state_description')"
                icon="👥"
            />
        @else
            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($partners as $partner)
                    <div
                        class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                        wire:key="partner-mobile-{{ $partner['id'] }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-surface-text dark:text-brand-text">{{ $partner['name'] }}</div>
                                <div class="truncate text-xs text-surface-muted dark:text-brand-muted">{{ $partner['email'] }}</div>
                            </div>
                            <x-ui.badge :type="$partner['status'] === 'active' ? 'active' : 'pending'">
                                {{ $partner['status'] === 'active' ? __('ui.status_active') : __('ui.status_pending') }}
                            </x-ui.badge>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.partners.table_level') }}</div>
                                <span class="mt-1 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-cyan/10 text-xs font-bold text-brand-cyan">
                                    L{{ $partner['level'] }}
                                </span>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.partners.table_registrations') }}</div>
                                <div class="mt-1 font-medium text-surface-text dark:text-brand-text">{{ number_format($partner['registrations']) }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.partners.table_earnings') }}</div>
                                <div class="mt-1 font-medium text-surface-text dark:text-brand-text">${{ number_format($partner['earnings'], 2) }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.partners.table_promo') }}</div>
                                <div class="mt-1">
                                    @if($partner['promo'])
                                        <strong class="tracking-widest text-brand-cyan">{{ $partner['promo'] }}</strong>
                                    @else
                                        <span class="text-surface-muted dark:text-brand-muted">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <x-ui.button variant="secondary" size="sm" class="w-full" wire:click="openEdit('{{ $partner['id'] }}')">
                                {{ __('ui.edit') }}
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[860px] table-fixed border-collapse text-start">
                    <colgroup>
                        <col class="w-[24%]">
                        <col class="w-[10%]">
                        <col class="w-[14%]">
                        <col class="w-[12%]">
                        <col class="w-[16%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                    </colgroup>
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.table_name') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.table_level') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.table_registrations') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.table_earnings') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.table_promo') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.status') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($partners as $partner)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="partner-{{ $partner['id'] }}">
                                <td class="py-3.5 pe-4 align-middle">
                                    <div class="truncate font-semibold">{{ $partner['name'] }}</div>
                                    <div class="truncate text-xs text-surface-muted dark:text-brand-muted">{{ $partner['email'] }}</div>
                                </td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-cyan/10 text-xs font-bold text-brand-cyan">
                                        L{{ $partner['level'] }}
                                    </span>
                                </td>
                                <td class="py-3.5 pe-4 align-middle">{{ number_format($partner['registrations']) }}</td>
                                <td class="py-3.5 pe-4 align-middle">${{ number_format($partner['earnings'], 2) }}</td>
                                <td class="py-3.5 pe-4 align-middle">
                                    @if($partner['promo'])
                                        <strong class="tracking-widest text-brand-cyan">{{ $partner['promo'] }}</strong>
                                    @else
                                        <span class="text-surface-muted dark:text-brand-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <x-ui.badge :type="$partner['status'] === 'active' ? 'active' : 'pending'">
                                        {{ $partner['status'] === 'active' ? __('ui.status_active') : __('ui.status_pending') }}
                                    </x-ui.badge>
                                </td>
                                <td class="py-3.5 align-middle">
                                    <x-ui.button variant="secondary" size="sm" wire:click="openEdit('{{ $partner['id'] }}')">
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

    <x-ui.modal wire:model="showEditModal" :title="__('ui.edit')" maxWidth="max-w-2xl">
        <div class="space-y-5 text-sm">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('admin.partners.table_name')" required>
                    <x-ui.input wire:model="editName" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('apply.form.email')" required>
                    <x-ui.input wire:model="editEmail" type="email" />
                </x-ui.form-group>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('apply.form.telegram_url')">
                    <x-ui.input wire:model="editTelegram" placeholder="https://t.me/username" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('apply.form.instagram_url')">
                    <x-ui.input wire:model="editInstagram" placeholder="https://instagram.com/username" />
                </x-ui.form-group>

                <x-ui.form-group :label="'Twitter (optional)'">
                    <x-ui.input wire:model="editTwitter" placeholder="https://twitter.com/username" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('ui.status')" required>
                    <x-ui.select wire:model="editStatus">
                        <option value="active">{{ __('ui.status_active') }}</option>
                        <option value="pending">{{ __('ui.status_pending') }}</option>
                        <option value="blocked">Blocked</option>
                    </x-ui.select>
                </x-ui.form-group>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="'Balance'" required>
                    <x-ui.input wire:model="editBalance" type="number" step="0.01" min="0" />
                </x-ui.form-group>

                <x-ui.form-group :label="'Total earned'" required>
                    <x-ui.input wire:model="editTotalEarned" type="number" step="0.01" min="0" />
                </x-ui.form-group>
            </div>

            <div class="flex items-center justify-between border-t border-surface-border pt-3 dark:border-brand-border/40">
                <span class="text-surface-muted dark:text-brand-muted">Created at</span>
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
