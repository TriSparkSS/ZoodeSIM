<?php

namespace Tests\Support;

use App\DataTransferObjects\FirebaseIdentity;
use App\Services\Auth\Contracts\FirebaseTokenVerifierInterface;
use Illuminate\Auth\AuthenticationException;

class FakeFirebaseTokenVerifier implements FirebaseTokenVerifierInterface
{
    public ?FirebaseIdentity $identity = null;

    public bool $fail = false;

    public function verify(string $idToken): FirebaseIdentity
    {
        if ($this->fail || $this->identity === null) {
            throw new AuthenticationException(__('api.user.social_invalid'));
        }

        return $this->identity;
    }
}
