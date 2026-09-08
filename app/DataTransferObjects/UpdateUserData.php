<?php

namespace App\DataTransferObjects;

readonly class UpdateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public ?string $password = null,
    ) {}
}
