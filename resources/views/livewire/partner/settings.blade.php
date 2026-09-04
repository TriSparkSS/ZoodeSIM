<div>
    <x-ui.page-header
        :title="__('partner.settings.title')"
        :subtitle="__('partner.settings.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <form wire:submit="save" class="space-y-7">
        <!-- Profile -->
        <x-ui.card :title="__('partner.settings.profile')">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('partner.settings.first_name')" required>
                    <x-ui.input wire:model="firstName" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('partner.settings.last_name')" required>
                    <x-ui.input wire:model="lastName" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('partner.settings.email')" required>
                    <x-ui.input wire:model="email" type="email" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('partner.settings.phone')" required>
                    <x-ui.input wire:model="phone" type="tel" />
                </x-ui.form-group>
            </div>
        </x-ui.card>

        <!-- Payout Settings -->
        <x-ui.card :title="__('partner.settings.payout')">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.form-group :label="__('partner.settings.payout_method')" required>
                    <x-ui.select wire:model="payoutMethod">
                        <option value="paypal">{{ __('partner.earnings.paypal') }}</option>
                        <option value="bank_transfer">{{ __('partner.earnings.bank_transfer') }}</option>
                    </x-ui.select>
                </x-ui.form-group>

                <x-ui.form-group :label="__('partner.settings.payout_details')" required>
                    <x-ui.input wire:model="payoutDetails" :placeholder="__('partner.settings.payout_details')" />
                </x-ui.form-group>
            </div>
        </x-ui.card>

        <!-- Notifications -->
        <x-ui.card :title="__('partner.settings.notifications')">
            <div class="space-y-4">
                <label class="flex cursor-pointer items-center justify-between rounded-xl bg-brand-card-alt px-4 py-3.5 dark:bg-brand-card-alt">
                    <div>
                        <div class="text-sm font-semibold">{{ __('partner.settings.email_notifications') }}</div>
                        <div class="text-xs text-brand-muted">{{ __('partner.settings.notifications') }}</div>
                    </div>
                    <input
                        type="checkbox"
                        wire:model="emailNotifications"
                        class="h-5 w-5 rounded border-brand-border bg-brand-card text-brand-cyan focus:ring-brand-cyan/30"
                    />
                </label>

                <label class="flex cursor-pointer items-center justify-between rounded-xl bg-brand-card-alt px-4 py-3.5 dark:bg-brand-card-alt">
                    <div>
                        <div class="text-sm font-semibold">{{ __('partner.settings.telegram_notifications') }}</div>
                        <div class="text-xs text-brand-muted">{{ __('partner.settings.notifications') }}</div>
                    </div>
                    <input
                        type="checkbox"
                        wire:model="telegramNotifications"
                        class="h-5 w-5 rounded border-brand-border bg-brand-card text-brand-cyan focus:ring-brand-cyan/30"
                    />
                </label>
            </div>
        </x-ui.card>

        <div class="flex justify-end">
            <x-ui.button type="submit" size="lg">
                💾 {{ __('ui.save') }}
            </x-ui.button>
        </div>
    </form>
</div>
