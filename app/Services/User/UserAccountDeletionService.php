<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\Auth\AuthActivityLogger;
use App\Services\User\Contracts\UserAccountDeletionServiceInterface;

class UserAccountDeletionService implements UserAccountDeletionServiceInterface
{
    public function __construct(
        protected AuthActivityLogger $logger,
    ) {}

    public function delete(User $user): void
    {
        $user->tokens()->delete();
        $this->logger->sessionTerminated(UserAuthService::GUARD, $user, [
            'account_deleted' => true,
        ]);
        $user->delete();
    }
}
