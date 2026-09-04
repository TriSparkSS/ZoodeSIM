<?php

namespace App\Policies;

use App\Models\Partner;

class PartnerPolicy
{
    public function view(Partner $actor, Partner $partner): bool
    {
        return $actor->id === $partner->id;
    }

    public function update(Partner $actor, Partner $partner): bool
    {
        return $actor->id === $partner->id;
    }
}
