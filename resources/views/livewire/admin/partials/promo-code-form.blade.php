@php
    $preferNeedingReplacement = $preferNeedingReplacement ?? false;
@endphp

<div class="space-y-5 text-sm">
    <x-ui.form-group :label="__('admin.promo_codes.form_partner')" required>
        <x-ui.select wire:model.live="formPartnerId">
            <option value="">{{ __('ui.select') }}</option>
            @foreach($partnerOptions as $partner)
                <option value="{{ $partner['id'] }}">
                    {{ $partner['name'] }}
                    @if($preferNeedingReplacement && $partner['needs_replacement'])
                        — {{ __('admin.promo_codes.needs_replacement') }}
                    @endif
                </option>
            @endforeach
        </x-ui.select>
        @error('formPartnerId')
            <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
        @enderror
    </x-ui.form-group>

    <x-ui.form-group :label="__('admin.promo_codes.form_code')" required>
        <div class="flex gap-2">
            <x-ui.input wire:model="formCode" class="flex-1 uppercase" placeholder="PARTNER42" />
            <x-ui.button variant="outline" wire:click="generateCode">
                ⚡ {{ __('ui.generate') }}
            </x-ui.button>
        </div>
        @error('formCode')
            <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
        @enderror
    </x-ui.form-group>

    <x-ui.form-group :label="__('admin.promo_codes.form_bonus_type')" required>
        <div class="flex flex-wrap gap-2">
            @foreach([
                \App\Models\PromoCode::BONUS_TYPE_MB => __('admin.promo_codes.bonus_type_mb'),
                \App\Models\PromoCode::BONUS_TYPE_USD => __('admin.promo_codes.bonus_type_usd'),
            ] as $value => $label)
                <button
                    type="button"
                    wire:click="setBonusType('{{ $value }}')"
                    @class([
                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                        'bg-brand-cyan/15 text-brand-cyan' => $formBonusType === $value,
                        'bg-surface-card-alt text-surface-muted hover:text-surface-text dark:bg-brand-card-alt dark:text-brand-muted dark:hover:text-brand-text' => $formBonusType !== $value,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
        @error('formBonusType')
            <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
        @enderror
    </x-ui.form-group>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <x-ui.form-group
            :label="$formBonusType === \App\Models\PromoCode::BONUS_TYPE_USD
                ? __('admin.promo_codes.form_bonus_amount_usd')
                : __('admin.promo_codes.form_bonus_mb')"
            required
        >
            <x-ui.input
                wire:model="formBonusAmount"
                type="number"
                min="{{ $formBonusType === \App\Models\PromoCode::BONUS_TYPE_USD ? '0.01' : '1' }}"
                step="{{ $formBonusType === \App\Models\PromoCode::BONUS_TYPE_USD ? '0.01' : '1' }}"
            />
            @error('formBonusAmount')
                <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
            @enderror
        </x-ui.form-group>

        <x-ui.form-group :label="__('admin.promo_codes.form_partner_reward')" required>
            <x-ui.input wire:model="formPartnerReward" type="number" step="0.01" min="0" />
            @error('formPartnerReward')
                <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
            @enderror
        </x-ui.form-group>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <x-ui.form-group :label="__('admin.promo_codes.form_type')" required>
            <x-ui.select wire:model="formType">
                <option value="standard">{{ __('admin.promo_codes.type_standard') }}</option>
                <option value="premium">{{ __('admin.promo_codes.type_premium') }}</option>
                <option value="seasonal">{{ __('admin.promo_codes.type_seasonal') }}</option>
                <option value="single">{{ __('admin.promo_codes.type_single') }}</option>
            </x-ui.select>
        </x-ui.form-group>

        <x-ui.form-group :label="__('admin.promo_codes.form_expires_at')">
            <x-ui.input wire:model="formExpiresAt" type="date" />
            @error('formExpiresAt')
                <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
            @enderror
        </x-ui.form-group>
    </div>

    <x-ui.form-group :label="__('admin.promo_codes.form_max_usage')">
        <x-ui.input wire:model="formMaxUsage" type="number" min="1" placeholder="{{ __('admin.promo_codes.unlimited') }}" />
        @error('formMaxUsage')
            <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
        @enderror
    </x-ui.form-group>

    @if($submitMethod === 'createPromo')
        <label class="flex items-start gap-3 text-sm text-surface-text dark:text-brand-text">
            <input
                type="checkbox"
                wire:model="formDeactivateExisting"
                class="mt-1 rounded border-surface-border text-brand-cyan focus:ring-brand-cyan dark:border-brand-border"
            >
            <span>{{ __('admin.promo_codes.form_deactivate_existing') }}</span>
        </label>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row">
        <x-ui.button variant="secondary" class="flex-1" wire:click="closeModals">
            {{ __('ui.cancel') }}
        </x-ui.button>
        <x-ui.button variant="success" class="flex-1" wire:click="{{ $submitMethod }}">
            {{ $submitLabel }}
        </x-ui.button>
    </div>
</div>
