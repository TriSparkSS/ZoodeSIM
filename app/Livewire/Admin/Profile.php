<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\WithLocalizedTitle;

use App\Livewire\Concerns\WithAdminNavigation;
use App\Livewire\Concerns\WithToast;
use App\Models\AuthActivityLog;
use App\Services\Auth\AuthActivityLogger;
use App\Services\Auth\AuthSessionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class Profile extends Component
{
    use WithAdminNavigation;
    use WithPagination;
    use WithLocalizedTitle;
    use WithToast;

    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $revokeOtherSessions = true;

    public bool $showTerminateModal = false;

    public ?int $terminatingSessionId = null;

    public function mount(): void
    {
        $admin = Auth::guard('admin')->user();

        $this->name = $admin->name;
        $this->email = $admin->email;
    }

    public function updateProfile(AuthActivityLogger $logger): void
    {
        $admin = Auth::guard('admin')->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email,'.$admin->id],
        ], [
            'name.required' => __('admin.profile.validation.name_required'),
            'email.required' => __('admin.profile.validation.email_required'),
            'email.email' => __('admin.profile.validation.email_invalid'),
            'email.unique' => __('admin.profile.validation.email_unique'),
        ]);

        $changed = [];
        if ($admin->name !== $validated['name']) {
            $changed['name'] = ['old' => $admin->name, 'new' => $validated['name']];
        }
        if ($admin->email !== $validated['email']) {
            $changed['email'] = ['old' => $admin->email, 'new' => $validated['email']];
        }

        $admin->update($validated);

        if ($changed !== []) {
            $logger->profileUpdated('admin', $admin, ['fields' => array_keys($changed)]);
        }

        $this->toast(__('admin.profile.saved'));
    }

    public function changePassword(AuthActivityLogger $logger, AuthSessionManager $sessions): void
    {
        $admin = Auth::guard('admin')->user();

        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'revokeOtherSessions' => ['boolean'],
        ], [
            'current_password.required' => __('admin.password.validation.current_required'),
            'password.required' => __('admin.password.validation.new_required'),
            'password.confirmed' => __('admin.password.validation.confirmed'),
        ]);

        if (! Hash::check($this->current_password, $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('admin.password.validation.current_invalid'),
            ]);
        }

        $admin->update([
            'password' => $this->password,
        ]);

        $logger->passwordChanged('admin', $admin);

        if ($this->revokeOtherSessions) {
            $sessions->terminateOthers($admin, 'admin');
        }

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->toast(__('admin.password.changed'));
    }

    public function confirmTerminate(int $sessionId): void
    {
        $this->terminatingSessionId = $sessionId;
        $this->showTerminateModal = true;
    }

    public function cancelTerminate(): void
    {
        $this->showTerminateModal = false;
        $this->terminatingSessionId = null;
    }

    public function terminateSession(AuthSessionManager $sessions): void
    {
        $admin = Auth::guard('admin')->user();
        $sessionId = $this->terminatingSessionId;

        if (! $sessionId) {
            return;
        }

        $session = $sessions->findOwned($admin, $sessionId);

        if (! $session || ! $session->isActive()) {
            $this->cancelTerminate();
            $this->toast(__('admin.sessions.not_found'), 'error');

            return;
        }

        if ($session->isCurrent()) {
            $this->cancelTerminate();
            $this->toast(__('admin.sessions.cannot_terminate_current'), 'error');

            return;
        }

        $sessions->terminate($session, $admin, 'admin');
        $this->cancelTerminate();
        $this->toast(__('admin.sessions.terminated'));
    }

    public function render(AuthSessionManager $sessions)
    {
        $admin = Auth::guard('admin')->user();

        return $this->withLocalizedTitle(view('livewire.admin.profile', [
            'breadcrumbs' => $this->adminBreadcrumbs(__('admin.profile.title')),
            'activeSessions' => $sessions->activeFor($admin),
            'currentSessionId' => session()->getId(),
            'activityLogs' => AuthActivityLog::query()
                ->where(function ($query) use ($admin) {
                    $query->whereMorphedTo('authenticatable', $admin)
                        ->orWhere(function ($failed) use ($admin) {
                            $failed->where('event', AuthActivityLog::EVENT_LOGIN_FAILED)
                                ->where('email_attempted', $admin->email)
                                ->where('guard', 'admin');
                        });
                })
                ->orderByDesc('created_at')
                ->paginate(10, pageName: 'logs'),
        ])->layout('layouts.admin', [
            'navItems' => $this->adminNavItems(),
            'title' => __('admin.profile.title'),
        ]), 'admin.profile.title');
    }
}
