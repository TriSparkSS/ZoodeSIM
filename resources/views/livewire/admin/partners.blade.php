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
                                <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.partners.table_wallet') }}</div>
                                <div class="mt-1 font-medium text-surface-text dark:text-brand-text">${{ number_format($partner['balance'], 2) }}</div>
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
                            @include('livewire.admin.partials.partner-actions', ['partnerId' => $partner['id']])
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[980px] table-fixed border-collapse text-start">
                    <colgroup>
                        <col class="w-[18%]">
                        <col class="w-[7%]">
                        <col class="w-[10%]">
                        <col class="w-[10%]">
                        <col class="w-[10%]">
                        <col class="w-[12%]">
                        <col class="w-[10%]">
                        <col class="w-[23%]">
                    </colgroup>
                    <thead>
                        <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.table_name') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.table_level') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.table_registrations') }}</th>
                            <th class="pb-3.5 pe-4 text-start font-medium">{{ __('admin.partners.table_wallet') }}</th>
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
                                <td class="py-3.5 pe-4 align-middle font-semibold text-brand-green">${{ number_format($partner['balance'], 2) }}</td>
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
                                    @include('livewire.admin.partials.partner-actions', ['partnerId' => $partner['id']])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <x-ui.modal wire:model="showProfileModal" :title="__('admin.partners.profile_modal_title', ['name' => $actingPartnerName])" maxWidth="max-w-2xl">
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

                <x-ui.form-group :label="__('admin.partners.twitter')">
                    <x-ui.input wire:model="editTwitter" placeholder="https://twitter.com/username" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('ui.status')" required>
                    <x-ui.select wire:model="editStatus">
                        <option value="active">{{ __('ui.status_active') }}</option>
                        <option value="pending">{{ __('ui.status_pending') }}</option>
                        <option value="blocked">{{ __('ui.status_blocked') }}</option>
                    </x-ui.select>
                </x-ui.form-group>
            </div>

            <div class="flex items-center justify-between border-t border-surface-border pt-3 dark:border-brand-border/40">
                <span class="text-surface-muted dark:text-brand-muted">{{ __('admin.partners.created_at') }}</span>
                <span class="font-semibold text-surface-text dark:text-brand-text">{{ $editCreatedAt ?? '—' }}</span>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button variant="secondary" class="flex-1" wire:click="closeProfile">
                    {{ __('ui.cancel') }}
                </x-ui.button>
                <x-ui.button variant="success" class="flex-1" wire:click="saveProfile">
                    {{ __('ui.save') }}
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

    <x-ui.modal wire:model="showPasswordModal" :title="__('admin.partners.password_modal_title', ['name' => $actingPartnerName])">
        <div class="space-y-5 text-sm">
            <p class="text-xs text-surface-muted dark:text-brand-muted">
                {{ __('admin.partners.password_hint') }}
            </p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('admin.partners.new_password')" required>
                    <x-ui.input wire:model="editPassword" type="password" autocomplete="new-password" />
                    @error('editPassword')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>

                <x-ui.form-group :label="__('admin.partners.confirm_password')" required>
                    <x-ui.input wire:model="editPasswordConfirmation" type="password" autocomplete="new-password" />
                    @error('editPasswordConfirmation')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
            </div>

            <label class="flex items-start gap-3 text-sm text-surface-text dark:text-brand-text">
                <input
                    type="checkbox"
                    wire:model="editRevokeSessions"
                    class="mt-1 rounded border-surface-border text-brand-cyan focus:ring-brand-cyan dark:border-brand-border"
                >
                <span>{{ __('admin.partners.revoke_sessions') }}</span>
            </label>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button variant="secondary" class="flex-1" wire:click="closePassword">
                    {{ __('ui.cancel') }}
                </x-ui.button>
                <x-ui.button variant="success" class="flex-1" wire:click="savePassword">
                    {{ __('admin.partners.action_password') }}
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>

    <x-ui.modal wire:model="showWalletModal" :title="__('admin.partners.wallet_modal_title', ['name' => $actingPartnerName])" maxWidth="max-w-2xl">
        <div class="space-y-5 text-sm">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="rounded-xl border border-surface-border bg-surface-card-alt/70 px-4 py-3 dark:border-brand-border dark:bg-brand-card-alt/40">
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.partners.wallet.current') }}</div>
                    <div class="mt-1 text-lg font-bold text-brand-green">${{ number_format((float) $walletBalance, 2) }}</div>
                </div>
                <div class="rounded-xl border border-surface-border bg-surface-card-alt/70 px-4 py-3 dark:border-brand-border dark:bg-brand-card-alt/40">
                    <div class="text-[11px] uppercase tracking-wide text-surface-muted dark:text-brand-muted">{{ __('admin.partners.table_earnings') }}</div>
                    <div class="mt-1 text-lg font-bold text-surface-text dark:text-brand-text">${{ number_format((float) $walletTotalEarned, 2) }}</div>
                </div>
            </div>

            <p class="text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.partners.wallet.help') }}</p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('admin.partners.wallet.direction')" required>
                    <x-ui.select wire:model="walletDirection">
                        <option value="credit">{{ __('admin.partners.wallet.add') }}</option>
                        <option value="debit">{{ __('admin.partners.wallet.deduct') }}</option>
                    </x-ui.select>
                </x-ui.form-group>
                <x-ui.form-group :label="__('admin.partners.wallet.amount')" required>
                    <x-ui.input wire:model="walletAmount" type="text" inputmode="decimal" />
                    @error('walletAmount')
                        <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                    @enderror
                </x-ui.form-group>
            </div>

            <x-ui.form-group :label="__('admin.partners.wallet.note')" required>
                <x-ui.input wire:model="walletNote" />
                @error('walletNote')
                    <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
                @enderror
            </x-ui.form-group>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button variant="secondary" class="flex-1" wire:click="closeWallet">
                    {{ __('ui.cancel') }}
                </x-ui.button>
                <x-ui.button variant="success" class="flex-1" wire:click="adjustWallet" wire:confirm="{{ __('admin.partners.wallet.confirm') }}">
                    {{ __('admin.partners.wallet.submit') }}
                </x-ui.button>
            </div>

            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-surface-muted dark:text-brand-muted">
                    {{ __('admin.partners.wallet.history') }}
                </div>
                @if(count($walletTransactions) === 0)
                    <p class="text-xs text-surface-muted dark:text-brand-muted">{{ __('admin.partners.wallet.empty') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[520px] table-fixed border-collapse text-start">
                            <thead>
                                <tr class="border-b border-surface-border text-[11px] uppercase tracking-wide text-surface-muted dark:border-brand-border dark:text-brand-muted">
                                    <th class="pb-2 pe-3 text-start font-medium">{{ __('ui.date') }}</th>
                                    <th class="pb-2 pe-3 text-start font-medium">{{ __('admin.partners.wallet.type') }}</th>
                                    <th class="pb-2 pe-3 text-start font-medium">{{ __('ui.amount') }}</th>
                                    <th class="pb-2 text-start font-medium">{{ __('admin.partners.wallet.balance_after') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-border/80 dark:divide-brand-border/40">
                                @foreach($walletTransactions as $entry)
                                    <tr class="text-xs text-surface-text dark:text-brand-text" wire:key="wallet-{{ $entry['id'] }}">
                                        <td class="py-2 pe-3 text-surface-muted dark:text-brand-muted">{{ $entry['date'] }}</td>
                                        <td class="py-2 pe-3">
                                            <span @class([
                                                'font-semibold',
                                                'text-brand-green' => $entry['type'] === 'credit',
                                                'text-brand-red' => $entry['type'] !== 'credit',
                                            ])>
                                                {{ $entry['type'] === 'credit' ? __('admin.partners.wallet.credit') : __('admin.partners.wallet.debit') }}
                                            </span>
                                            <div class="text-[11px] text-surface-muted dark:text-brand-muted">
                                                {{ __('admin.partners.wallet.categories.'.$entry['category']) }}
                                                @if($entry['description'])
                                                    · {{ $entry['description'] }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-2 pe-3 font-semibold">
                                            {{ $entry['type'] === 'credit' ? '+' : '−' }}${{ number_format($entry['amount'], 2) }}
                                        </td>
                                        <td class="py-2 font-semibold">${{ number_format($entry['balance_after'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </x-ui.modal>
</div>
