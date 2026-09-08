<?php

namespace App\Services\Referral\Contracts;

use App\Models\Partner;

interface ReferralMilestoneServiceInterface
{
    public function awardIfDue(Partner $partner): void;
}
