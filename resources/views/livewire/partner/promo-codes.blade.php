<div>
    <x-ui.page-header
        :title="__('partner.promo_codes.title')"
        :subtitle="__('partner.promo_codes.subtitle')"
        :breadcrumbs="$breadcrumbs"
    >
        <x-slot:actions>
            <x-ui.button wire:click="openCreateModal">
                + {{ __('partner.promo_codes.create') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Promo Codes Table -->
    <x-ui.card :title="__('partner.dashboard.my_promo_codes')">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-brand-border text-start text-[11px] uppercase tracking-wide text-brand-muted">
                        <th class="pb-3.5 pe-4">{{ __('partner.dashboard.table_code') }}</th>
                        <th class="pb-3.5 pe-4">{{ __('partner.dashboard.table_uses') }}</th>
                        <th class="pb-3.5 pe-4">{{ __('partner.dashboard.table_bonus') }}</th>
                        <th class="pb-3.5 pe-4">{{ __('partner.dashboard.table_earnings') }}</th>
                        <th class="pb-3.5">{{ __('ui.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border/40">
                    @foreach($promoCodes as $promo)
                        <tr class="text-sm">
                            <td class="py-3.5 pe-4">
                                <strong class="tracking-widest text-brand-cyan">{{ $promo['code'] }}</strong>
                            </td>
                            <td class="py-3.5 pe-4">{{ $promo['uses'] }}</td>
                            <td class="py-3.5 pe-4">{{ $promo['bonus'] }}</td>
                            <td class="py-3.5 pe-4">${{ number_format($promo['earnings'], 2) }}</td>
                            <td class="py-3.5">
                                <x-ui.badge :type="$promo['status'] === 'active' ? 'active' : 'pending'">
                                    {{ $promo['status'] === 'active' ? __('ui.status_active') : __('ui.status_pending') }}
                                </x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <!-- Create Modal -->
    <x-ui.modal wire:model="showCreateModal" :title="__('partner.promo_codes.create_modal_title')">
        <div class="space-y-5">
            <x-ui.form-group :label="__('partner.promo_codes.code_label')" required>
                <x-ui.input wire:model="newCode" :placeholder="__('partner.promo_codes.code_label')" />
            </x-ui.form-group>

            <x-ui.form-group :label="__('partner.promo_codes.bonus_label')" required>
                <x-ui.input wire:model="newBonus" type="number" :placeholder="__('partner.promo_codes.bonus_label')" />
            </x-ui.form-group>

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-ui.button variant="secondary" wire:click="closeCreateModal">
                    {{ __('ui.cancel') }}
                </x-ui.button>
                <x-ui.button>
                    {{ __('partner.promo_codes.submit_create') }}
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
