<?php

namespace App\DataTransferObjects;

readonly class FirebaseIdentity
{
    public function __construct(
        public string $uid,
        public ?string $email,
        public ?string $name,
        public bool $emailVerified,
        public string $signInProvider,
    ) {}

    public function provider(): string
    {
        return match ($this->signInProvider) {
            'google.com' => 'google',
            'apple.com' => 'apple',
            default => $this->signInProvider,
        };
    }
}
