<?php

namespace App\Services\ResellPortal\Contracts;

use App\DataTransferObjects\ResellPortalBalance;

interface ResellPortalBalanceServiceInterface
{
    public function current(): ResellPortalBalance;

    public function refresh(): ResellPortalBalance;
}
