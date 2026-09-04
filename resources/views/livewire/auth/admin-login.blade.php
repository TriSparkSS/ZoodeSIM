<div class="mx-auto w-full max-w-md">
    <div class="mb-8 text-center">
        <div class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-2xl gradient-bg text-2xl text-white shadow-lg shadow-brand-purple/20">
            🛡️
        </div>
        <h1 class="text-2xl font-bold text-surface-text dark:text-brand-text">{{ __('auth.admin.title') }}</h1>
        <p class="mt-2 text-sm text-brand-muted">{{ __('auth.admin.subtitle') }}</p>
    </div>

    <div class="auth-card">
        <form wire:submit="login" class="space-y-5">
            <x-ui.form-group :label="__('auth.email')" required :error="$errors->first('email')">
                <x-ui.input
                    type="email"
                    wire:model="email"
                    :placeholder="__('auth.email_placeholder')"
                    autocomplete="email"
                    :error="$errors->first('email')"
                />
            </x-ui.form-group>

            <x-ui.form-group :label="__('auth.password')" required :error="$errors->first('password')">
                <x-ui.input
                    type="password"
                    wire:model="password"
                    :placeholder="__('auth.password_placeholder')"
                    autocomplete="current-password"
                    :error="$errors->first('password')"
                />
            </x-ui.form-group>

            <div class="flex items-center justify-between gap-4">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-brand-muted">
                    <input
                        type="checkbox"
                        wire:model="remember"
                        class="h-4 w-4 rounded border-brand-border bg-brand-card-alt accent-brand-cyan focus:ring-brand-cyan/30"
                    />
                    {{ __('auth.remember_me') }}
                </label>
                <a href="#" class="text-sm text-brand-cyan transition-colors hover:text-brand-purple">
                    {{ __('auth.forgot_password') }}
                </a>
            </div>

            <x-ui.button type="submit" class="mt-1 w-full font-extrabold" size="lg" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">{{ __('auth.sign_in') }}</span>
                <span wire:loading wire:target="login" class="inline-flex items-center gap-2">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    {{ __('ui.loading') }}
                </span>
            </x-ui.button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-brand-muted">
        {{ __('auth.partner_access') }}
        <a href="{{ route('login') }}" class="font-semibold text-brand-cyan hover:text-brand-purple">
            {{ __('ui.partner_login') }}
        </a>
    </p>
</div>
