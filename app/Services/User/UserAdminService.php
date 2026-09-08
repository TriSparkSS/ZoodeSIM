<?php

namespace App\Services\User;

use App\DataTransferObjects\UpdateUserData;
use App\Models\Admin;
use App\Models\AuthActivityLog;
use App\Models\User;
use App\Services\Auth\AuthActivityLogger;
use App\Services\User\Contracts\UserAdminServiceInterface;

class UserAdminService implements UserAdminServiceInterface
{
    public function __construct(
        protected AuthActivityLogger $activity,
    ) {}

    public function update(User $user, Admin $admin, UpdateUserData $data): User
    {
        $payload = [
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
        ];

        if (filled($data->password)) {
            $payload['password'] = $data->password;
        }

        $user->update($payload);

        $this->activity->log(
            'admin',
            AuthActivityLog::EVENT_USER_UPDATED,
            $admin,
            meta: [
                'user_id' => $user->id,
                'fields' => array_keys($payload),
            ],
        );

        return $user->fresh();
    }
}
