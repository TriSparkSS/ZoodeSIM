<x-ui.modal wire:model="showWithdrawModal" :title="__('partner.earnings.modal_title')">
    <form wire:submit="submitWithdrawal" class="space-y-5 text-sm">
        <x-ui.form-group :label="__('partner.earnings.amount_label')" required>
            <x-ui.input wire:model="withdrawAmount" type="text" inputmode="decimal" />
            @error('withdrawAmount')
                <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
            @enderror
        </x-ui.form-group>

        <x-ui.form-group :label="__('partner.earnings.payment_method')" required>
            <x-ui.select wire:model="withdrawMethod">
                @foreach($methods as $method)
                    <option value="{{ $method }}">{{ \App\Models\Withdrawal::methodLabel($method) }}</option>
                @endforeach
            </x-ui.select>
            @error('withdrawMethod')
                <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
            @enderror
        </x-ui.form-group>

        <x-ui.form-group :label="__('partner.earnings.details_label')" required>
            <x-ui.input wire:model="withdrawDetails" />
            @error('withdrawDetails')
                <p class="mt-1 text-xs text-brand-red">{{ $message }}</p>
            @enderror
        </x-ui.form-group>

        <p class="text-xs text-surface-muted dark:text-brand-muted">
            {{ __('partner.dashboard.min_withdrawal', ['amount' => $minWithdrawal]) }}
        </p>

        <div class="flex justify-end gap-2">
            <x-ui.button variant="secondary" type="button" wire:click="closeWithdrawModal">
                {{ __('ui.cancel') }}
            </x-ui.button>
            <x-ui.button variant="success" type="submit">
                {{ __('partner.earnings.submit_request') }}
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>
