<?php

namespace App\Services\User\Contracts;

use App\Models\User;

interface UserAccountDeletionServiceInterface
{
    public function delete(User $user): void;
}
