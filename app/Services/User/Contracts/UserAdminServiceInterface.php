<?php

namespace App\Services\User\Contracts;

use App\DataTransferObjects\UpdateUserData;
use App\Models\Admin;
use App\Models\User;

interface UserAdminServiceInterface
{
    public function update(User $user, Admin $admin, UpdateUserData $data): User;
}
