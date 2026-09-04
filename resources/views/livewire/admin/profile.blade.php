<div>
    <x-ui.page-header
        :title="__('admin.profile.title')"
        :subtitle="__('admin.profile.subtitle')"
        :breadcrumbs="$breadcrumbs"
    />

    <div class="space-y-6">
        {{-- Profile --}}
        <x-ui.card :title="__('admin.profile.section')">
            <form wire:submit="updateProfile" class="max-w-xl space-y-5">
                <x-ui.form-group :label="__('admin.profile.name')" name="name" required :error="$errors->first('name')">
                    <x-ui.input wire:model="name" name="name" :error="$errors->first('name')" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('admin.profile.email')" name="email" required :error="$errors->first('email')">
                    <x-ui.input type="email" wire:model="email" name="email" :error="$errors->first('email')" />
                </x-ui.form-group>

                <x-ui.button type="submit" wire:loading.attr="disabled">
                    {{ __('ui.save') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        {{-- Change password --}}
        <x-ui.card :title="__('admin.password.section')">
            <form wire:submit="changePassword" class="max-w-xl space-y-5">
                <x-ui.form-group :label="__('admin.password.current')" name="current_password" required :error="$errors->first('current_password')">
                    <x-ui.input type="password" wire:model="current_password" name="current_password" autocomplete="current-password" :error="$errors->first('current_password')" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('admin.password.new')" name="password" required :error="$errors->first('password')">
                    <x-ui.input type="password" wire:model="password" name="password" autocomplete="new-password" :error="$errors->first('password')" />
                </x-ui.form-group>

                <x-ui.form-group :label="__('admin.password.confirm')" name="password_confirmation" required :error="$errors->first('password_confirmation')">
                    <x-ui.input type="password" wire:model="password_confirmation" name="password_confirmation" autocomplete="new-password" />
                </x-ui.form-group>

                <label class="flex cursor-pointer items-center gap-2 text-sm text-brand-muted">
                    <input
                        type="checkbox"
                        wire:model="revokeOtherSessions"
                        class="h-4 w-4 rounded border-brand-border bg-brand-card-alt accent-brand-cyan focus:ring-brand-cyan/30"
                    />
                    {{ __('admin.password.revoke_others') }}
                </label>

                <x-ui.button type="submit" wire:loading.attr="disabled">
                    {{ __('admin.password.update') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        {{-- Active sessions --}}
        <x-ui.card :title="__('admin.sessions.active')">
            @if($activeSessions->isEmpty())
                <p class="text-sm text-brand-muted">{{ __('admin.sessions.empty') }}</p>
            @else
                <div class="divide-y divide-surface-border dark:divide-brand-border">
                    @foreach($activeSessions as $session)
                        <div class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-surface-text dark:text-brand-text">
                                        {{ $session->device_label ?? __('admin.sessions.unknown_device') }}
                                    </span>
                                    @if($session->session_id === $currentSessionId)
                                        <span class="rounded-md bg-brand-cyan/10 px-2 py-0.5 text-xs font-semibold text-brand-cyan">
                                            {{ __('admin.sessions.this_device') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-brand-muted">
                                    {{ $session->ip_address }}
                                    · {{ __('admin.sessions.last_active') }}:
                                    {{ $session->last_activity_at?->diffForHumans() }}
                                </p>
                                <p class="mt-0.5 text-xs text-brand-muted">
                                    {{ __('admin.sessions.logged_in') }}:
                                    {{ $session->login_at?->format('Y-m-d H:i') }}
                                </p>
                            </div>

                            @if($session->session_id !== $currentSessionId)
                                <x-ui.button
                                    type="button"
                                    variant="danger"
                                    size="sm"
                                    wire:click="confirmTerminate({{ $session->id }})"
                                >
                                    {{ __('admin.sessions.terminate') }}
                                </x-ui.button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        {{-- Activity / login history --}}
        <x-ui.card :title="__('admin.logs.title')">
            @if($activityLogs->isEmpty())
                <p class="text-sm text-brand-muted">{{ __('admin.logs.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-surface-border text-xs uppercase tracking-wide text-brand-muted dark:border-brand-border">
                                <th class="px-2 py-3 font-semibold">{{ __('admin.logs.event') }}</th>
                                <th class="px-2 py-3 font-semibold">{{ __('admin.logs.ip') }}</th>
                                <th class="px-2 py-3 font-semibold">{{ __('admin.logs.device') }}</th>
                                <th class="px-2 py-3 font-semibold">{{ __('admin.logs.time') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-border dark:divide-brand-border">
                            @foreach($activityLogs as $log)
                                <tr>
                                    <td class="px-2 py-3 font-medium text-surface-text dark:text-brand-text">
                                        {{ __('admin.logs.events.'.$log->event) }}
                                        @if($log->event === 'login_failed' && $log->email_attempted)
                                            <span class="block text-xs font-normal text-brand-muted">{{ $log->email_attempted }}</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-brand-muted">{{ $log->ip_address ?? '—' }}</td>
                                    <td class="px-2 py-3 text-brand-muted">
                                        <span class="line-clamp-1 max-w-[220px]" title="{{ $log->user_agent }}">
                                            {{ $log->user_agent ? \Illuminate\Support\Str::limit($log->user_agent, 40) : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 whitespace-nowrap text-brand-muted">
                                        {{ $log->created_at?->format('Y-m-d H:i:s') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $activityLogs->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.modal wire:model="showTerminateModal" :title="__('admin.sessions.terminate_title')">
        <p class="mb-6 text-sm text-brand-muted">{{ __('admin.sessions.terminate_confirm') }}</p>
        <div class="flex justify-end gap-3">
            <x-ui.button type="button" variant="ghost" wire:click="cancelTerminate">
                {{ __('ui.cancel') }}
            </x-ui.button>
            <x-ui.button type="button" variant="danger" wire:click="terminateSession">
                {{ __('admin.sessions.terminate') }}
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
