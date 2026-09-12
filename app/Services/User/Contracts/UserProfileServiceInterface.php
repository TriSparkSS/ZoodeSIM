<?php

namespace App\Services\User\Contracts;

use App\DataTransferObjects\UpdateUserData;
use App\Models\User;

interface UserProfileServiceInterface
{
    public function update(User $user, UpdateUserData $data, ?string $currentPassword = null): User;
}
