<?php

namespace App\Services\Partner;

use App\Models\Partner;
use App\Models\Transaction;
use App\Services\Partner\Contracts\PartnerLedgerServiceInterface;
use App\Services\Wallet\Contracts\WalletLedgerServiceInterface;
use App\Support\Money;

class PartnerLedgerService implements PartnerLedgerServiceInterface
{
    public function __construct(
        protected WalletLedgerServiceInterface $ledger,
    ) {}

    public function credit(
        Partner $partner,
        Money $amount,
        string $category,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null,
        bool $countsAsEarning = false,
    ): Transaction {
        return $this->ledger->credit(
            $partner,
            $amount,
            $category,
            $referenceType,
            $referenceId,
            $description,
            $countsAsEarning,
        );
    }

    public function debit(
        Partner $partner,
        Money $amount,
        string $category,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $description = null,
    ): Transaction {
        return $this->ledger->debit(
            $partner,
            $amount,
            $category,
            $referenceType,
            $referenceId,
            $description,
        );
    }
}
