<?php

namespace Tests\Concerns;

use App\Models\User;

trait FundsUserWallet
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function fundedUser(array $attributes = [], string $balance = '500.00'): User
    {
        return User::factory()->create(array_merge(['balance' => $balance], $attributes));
    }
}
