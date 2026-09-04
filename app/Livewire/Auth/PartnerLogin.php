<?php

namespace App\Livewire\Auth;

use App\Livewire\Concerns\WithLocalizedTitle;
use App\Models\Partner;
use App\Services\Auth\GuardLoginService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class PartnerLogin extends Component
{
    use WithLocalizedTitle;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (Auth::guard('partner')->check()) {
            $this->redirectRoute('partner.dashboard', absolute: false);
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
            guard: 'partner',
            credentials: [
                'email' => $this->email,
                'password' => $this->password,
            ],
            remember: $this->remember,
            logoutOtherGuards: ['admin'],
            afterAuthenticate: function ($user): void {
                if (! $user instanceof Partner || ! $user->isActive()) {
                    throw ValidationException::withMessages([
                        'email' => __('auth.failed'),
                    ]);
                }
            },
        );

        $this->redirectRoute('partner.dashboard', absolute: false);
    }

    public function render()
    {
        return $this->withLocalizedTitle(view('livewire.auth.partner-login', [
            'portalTitle' => __('ui.partner_portal'),
        ]), 'auth.partner.title');
    }
}
