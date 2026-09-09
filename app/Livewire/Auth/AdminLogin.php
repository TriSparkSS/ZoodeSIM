<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\WithLocalizedTitle;
use App\Services\Auth\GuardLoginService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class AdminLogin extends Component
{
    use WithLocalizedTitle;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (Auth::guard('admin')->check()) {
            $this->redirectRoute('admin.dashboard', absolute: false);
        }
    }

    public function login(GuardLoginService $login): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.required' => __('auth.validation.email_required'),
            'email.email' => __('auth.validation.email_invalid'),
            'password.required' => __('auth.validation.password_required'),
            'password.min' => __('auth.validation.password_min'),
        ]);

        $login->attempt(
            guard: 'admin',
            credentials: [
                'email' => $this->email,
                'password' => $this->password,
            ],
            remember: $this->remember,
            logoutOtherGuards: ['partner'],
        );

        $this->redirectRoute('admin.dashboard', absolute: false);
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.auth.admin-login', [
            'portalTitle' => __('ui.admin_panel'),
        ]), 'auth.admin.title');
    }
}
