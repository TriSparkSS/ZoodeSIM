<?php

namespace App\Services\Partner\Contracts;

use App\Models\Partner;
use App\Models\Transaction;
use App\Support\Money;

interface PartnerLedgerServiceInterface
{
    public function credit(
        Partner $partner,
        Money $amount,
        string $category,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null,
        bool $countsAsEarning = false,
    ): Transaction;

    public function debit(
        Partner $partner,
        Money $amount,
        string $category,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null,
    ): Transaction;
}
