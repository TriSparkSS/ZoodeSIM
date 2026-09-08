<?php

namespace App\Policies;

use App\Models\EsimOrder;
use App\Models\User;

class EsimOrderPolicy
{
    public function view(User $user, EsimOrder $order): bool
    {
        return $user->id === $order->user_id;
    }
}
