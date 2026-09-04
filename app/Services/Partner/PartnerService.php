<?php

namespace App\Services\Partner;

use App\Models\Partner;
use App\Models\PartnerApplication;
use App\Services\Auth\AuthActivityLogger;
use App\Services\Auth\AuthSessionManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PartnerService
{
    public function __construct(
        protected AuthSessionManager $sessions,
        protected AuthActivityLogger $activityLogger,
    ) {}

    /**
     * @return array{partner: Partner, plain_password: string}
     */
    public function createFromApplication(PartnerApplication $application, ?string $password = null): array
    {
        $plainPassword = $password ?? Str::password(12);

        $partner = Partner::query()->create([
            'name' => trim($application->first_name.' '.($application->last_name ?? '')),
            'email' => $application->email,
            'password' => $plainPassword,
            'social_contacts' => [
                'telegram' => $application->telegram,
                'instagram' => $application->instagram,
                'twitter' => null,
            ],
            'status' => 'active',
            'balance' => 0,
            'total_earned' => 0,
        ]);

        return [
            'partner' => $partner,
            'plain_password' => $plainPassword,
        ];
    }

    /**
     * Admin-driven password reset for a partner account.
     *
     * @throws ValidationException
     */
    public function updatePassword(
        Partner $partner,
        string $password,
        bool $revokeSessions = true,
    ): Partner {
        $password = trim($password);

        if (strlen($password) < 8) {
            throw ValidationException::withMessages([
                'editPassword' => __('admin.partners.validation.password_min'),
            ]);
        }

        $partner->update([
            'password' => $password,
        ]);

        if ($revokeSessions) {
            $this->sessions->terminateAll($partner, 'partner');
        }

        $this->activityLogger->passwordChanged('partner', $partner, [
            'reset_by' => 'admin',
            'sessions_revoked' => $revokeSessions,
        ]);

        return $partner->fresh();
    }
}
