@props(['partnerId'])

<div class="flex flex-wrap gap-1.5">
    <x-ui.button variant="secondary" size="sm" wire:click="openProfile('{{ $partnerId }}')">
        {{ __('admin.partners.action_profile') }}
    </x-ui.button>
    <x-ui.button variant="secondary" size="sm" wire:click="openPassword('{{ $partnerId }}')">
        {{ __('admin.partners.action_password') }}
    </x-ui.button>
    <x-ui.button variant="success" size="sm" wire:click="openWallet('{{ $partnerId }}')">
        {{ __('admin.partners.action_wallet') }}
    </x-ui.button>
</div>
