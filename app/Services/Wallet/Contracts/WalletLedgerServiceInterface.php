<?php

namespace App\Services\Wallet\Contracts;

use App\Models\Partner;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;

interface WalletLedgerServiceInterface
{
    public function credit(
        User|Partner $owner,
        Money $amount,
        string $category,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null,
        bool $countsAsEarning = false,
        ?string $promoCodeId = null,
        array $meta = [],
    ): Transaction;

    public function debit(
        User|Partner $owner,
        Money $amount,
        string $category,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null,
        ?string $promoCodeId = null,
        array $meta = [],
    ): Transaction;
}
