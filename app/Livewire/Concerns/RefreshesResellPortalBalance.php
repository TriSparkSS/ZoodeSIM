<?php

namespace App\Livewire\Concerns;

use App\Services\ResellPortal\Contracts\ResellPortalBalanceServiceInterface;

trait RefreshesResellPortalBalance
{
    public function refreshBalance(ResellPortalBalanceServiceInterface $balance): void
    {
        $result = $balance->refresh();

        if ($result->available) {
            $this->toast(__('admin.statistics.balance_refreshed'));

            return;
        }

        $this->toast(__('admin.statistics.balance_unavailable'), 'error');
    }
}
