<?php

namespace App\Services\Wallet\Contracts;

interface TransactionIdGeneratorInterface
{
    public function next(): string;
}
