<div>
    <x-ui.page-header
        :title="__('admin.promo_codes.title')"
        :subtitle="__('admin.promo_codes.subtitle')"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" wire:click="openAssignModal">
                {{ __('admin.promo_codes.assign') }}
            </x-ui.button>
            <x-ui.button wire:click="openCreateModal">
                {{ __('admin.promo_codes.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <x-ui.search-input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('admin.promo_codes.search_placeholder')"
                class="w-full max-w-sm"
            />

            <div class="flex flex-wrap gap-2">
                @foreach([
                    'all' => __('admin.promo_codes.filter_all'),
                    'active' => __('admin.promo_codes.filter_active'),
                    'expired' => __('admin.promo_codes.filter_expired'),
                    'exhausted' => __('admin.promo_codes.filter_exhausted'),
                    'inactive' => __('admin.promo_codes.filter_inactive'),
                    'locked' => __('admin.promo_codes.filter_locked'),
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
        </div>

        @if(count($promoCodes) === 0)
            <x-ui.empty-state
                :title="__('ui.no_results')"
                :description="__('ui.empty_state_description')"
                icon="🎟️"
            />
        @else
            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($promoCodes as $promo)
                    <div
                        class="rounded-xl border border-surface-border bg-surface-card-alt/70 p-4 dark:border-brand-border dark:bg-brand-card-alt/40"
                        wire:key="promo-mobile-{{ $promo['id'] }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-surface-text dark:text-brand-text">{{ $promo['partner'] }}</div>
                                <strong class="mt-1 block tracking-widest text-brand-cyan">{{ $promo['code'] }}</strong>
                                @if($promo['is_locked'])
                                    <div class="mt-1 text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.promo_codes.unlock_at', ['count' => $promo['unlock_requirement']]) }}</div>
                                @endif
                            </div>
                            <x-ui.badge :type="$promo['status']">
                                @if($promo['status'] === 'active')
                                    {{ __('ui.status_active') }}
                                @elseif($promo['status'] === 'expired')
                                    {{ __('admin.promo_codes.status_expired') }}
                                @elseif($promo['status'] === 'exhausted')
                                    {{ __('admin.promo_codes.status_exhausted') }}
                                @elseif($promo['status'] === 'locked')
                                    {{ __('admin.promo_codes.status_locked') }}
                                @else
                                    {{ __('admin.promo_codes.status_inactive') }}
                                @endif
                            </x-ui.badge>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.promo_codes.table_uses') }}</div>
                                <div class="mt-1 font-medium text-surface-text dark:text-brand-text">{{ $promo['uses_label'] }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.promo_codes.table_bonus') }}</div>
                                <div class="mt-1 font-medium text-surface-text dark:text-brand-text">{{ $promo['bonus'] }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.promo_codes.table_expires') }}</div>
                                <div class="mt-1 font-medium text-surface-text dark:text-brand-text">{{ $promo['expires_at'] ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($promo['needs_replacement'])
                                <x-ui.button size="sm" variant="outline" wire:click="openAssignModal('{{ $promo['partner_id'] }}')">
                                    {{ __('admin.promo_codes.assign_to_partner') }}
                                </x-ui.button>
                            @endif
                            @if($promo['is_active'])
                                <x-ui.button size="sm" variant="secondary" wire:click="deactivate('{{ $promo['id'] }}')">
                                    {{ __('admin.promo_codes.deactivate') }}
                                </x-ui.button>
                            @elseif(! $promo['is_locked'])
                                <x-ui.button size="sm" variant="secondary" wire:click="activate('{{ $promo['id'] }}')">
                                    {{ __('admin.promo_codes.activate') }}
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[960px] table-fixed border-collapse text-start">
                    <colgroup>
                        <col class="w-[18%]">
                        <col class="w-[14%]">
                        <col class="w-[12%]">
                        <col class="w-[10%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                        <col class="w-[22%]">
                    </colgroup>
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.promo_codes.table_partner') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.promo_codes.table_code') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.promo_codes.table_uses') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.promo_codes.table_bonus') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.promo_codes.table_expires') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('ui.status') }}</th>
                            <th class="pb-3.5 text-start font-medium">{{ __('admin.promo_codes.table_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                        @foreach($promoCodes as $promo)
                            <tr class="text-sm text-surface-text dark:text-brand-text" wire:key="promo-{{ $promo['id'] }}">
                                <td class="py-3.5 pe-4 align-middle font-semibold">{{ $promo['partner'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <strong class="tracking-widest text-brand-cyan">{{ $promo['code'] }}</strong>
                                    @if($promo['is_locked'])
                                        <div class="mt-1 text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.promo_codes.unlock_at', ['count' => $promo['unlock_requirement']]) }}</div>
                                    @endif
                                </td>
                                <td class="py-3.5 pe-4 align-middle">{{ $promo['uses_label'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $promo['bonus'] }}</td>
                                <td class="py-3.5 pe-4 align-middle">{{ $promo['expires_at'] ?? '—' }}</td>
                                <td class="py-3.5 pe-4 align-middle">
                                    <x-ui.badge :type="$promo['status']">
                                        @if($promo['status'] === 'active')
                                            {{ __('ui.status_active') }}
                                        @elseif($promo['status'] === 'expired')
                                            {{ __('admin.promo_codes.status_expired') }}
                                        @elseif($promo['status'] === 'exhausted')
                                            {{ __('admin.promo_codes.status_exhausted') }}
                                        @elseif($promo['status'] === 'locked')
                                            {{ __('admin.promo_codes.status_locked') }}
                                        @else
                                            {{ __('admin.promo_codes.status_inactive') }}
                                        @endif
                                    </x-ui.badge>
                                </td>
                                <td class="py-3.5 align-middle">
                                    <div class="flex flex-wrap gap-2">
                                        @if($promo['needs_replacement'])
                                            <x-ui.button size="sm" variant="outline" wire:click="openAssignModal('{{ $promo['partner_id'] }}')">
                                                {{ __('admin.promo_codes.assign') }}
                                            </x-ui.button>
                                        @endif
                                        @if($promo['is_active'])
                                            <x-ui.button size="sm" variant="secondary" wire:click="deactivate('{{ $promo['id'] }}')">
                                                {{ __('admin.promo_codes.deactivate') }}
                                            </x-ui.button>
                                        @elseif(! $promo['is_locked'])
                                            <x-ui.button size="sm" variant="secondary" wire:click="activate('{{ $promo['id'] }}')">
                                                {{ __('admin.promo_codes.activate') }}
                                            </x-ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <x-ui.modal wire:model="showCreateModal" :title="__('admin.promo_codes.create_modal_title')" maxWidth="max-w-2xl">
        @include('livewire.admin.partials.promo-code-form', ['submitMethod' => 'createPromo', 'submitLabel' => __('admin.promo_codes.create')])
    </x-ui.modal>

    <x-ui.modal wire:model="showAssignModal" :title="__('admin.promo_codes.assign_modal_title')" maxWidth="max-w-2xl">
        <p class="mb-4 text-sm text-surface-muted dark:text-brand-muted">{{ __('admin.promo_codes.assign_help') }}</p>
        @include('livewire.admin.partials.promo-code-form', [
            'submitMethod' => 'assignPromo',
            'submitLabel' => __('admin.promo_codes.assign'),
            'preferNeedingReplacement' => true,
        ])
    </x-ui.modal>
</div>
