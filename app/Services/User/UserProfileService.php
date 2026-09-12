<?php

namespace App\Services\User;

use App\DataTransferObjects\UpdateUserData;
use App\Models\User;
use App\Services\Auth\AuthActivityLogger;
use App\Services\User\Contracts\UserProfileServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserProfileService implements UserProfileServiceInterface
{
    public function __construct(
        protected AuthActivityLogger $activity,
    ) {}

    public function update(User $user, UpdateUserData $data, ?string $currentPassword = null): User
    {
        if (filled($data->password)) {
            if ($currentPassword === null || ! Hash::check($currentPassword, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => __('api.validation.current_password_invalid'),
                ]);
            }
        }

        $payload = [
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
        ];

        if (filled($data->password)) {
            $payload['password'] = $data->password;
        }

        $changed = [];

        if ($user->name !== $payload['name']) {
            $changed[] = 'name';
        }

        if ($user->email !== $payload['email']) {
            $changed[] = 'email';
        }

        if ($user->phone !== $payload['phone']) {
            $changed[] = 'phone';
        }

        if (array_key_exists('password', $payload)) {
            $changed[] = 'password';
        }

        $user->update($payload);

        if ($changed !== []) {
            $this->activity->profileUpdated(UserAuthService::GUARD, $user, ['fields' => $changed]);
        }

        return $user->fresh() ?? $user;
    }
}
