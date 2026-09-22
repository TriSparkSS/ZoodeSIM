<?php

namespace App\Rules;

use App\Services\Auth\AccountEmailUniqueness;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueAccountEmail implements ValidationRule
{
    public function __construct(
        protected string $messageKey = 'api.validation.email_unique',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if (app(AccountEmailUniqueness::class)->isTaken($value)) {
            $fail(__($this->messageKey));
        }
    }
}
