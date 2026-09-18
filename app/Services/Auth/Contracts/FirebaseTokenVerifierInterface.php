<?php

namespace App\Services\Auth\Contracts;

use App\DataTransferObjects\FirebaseIdentity;
use Illuminate\Auth\AuthenticationException;

interface FirebaseTokenVerifierInterface
{
    /**
     * @throws AuthenticationException
     */
    public function verify(string $idToken): FirebaseIdentity;
}
