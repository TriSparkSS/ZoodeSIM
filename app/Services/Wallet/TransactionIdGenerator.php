<?php

namespace App\Services\Wallet;

use App\Services\Wallet\Contracts\TransactionIdGeneratorInterface;
use Illuminate\Support\Facades\DB;

class TransactionIdGenerator implements TransactionIdGeneratorInterface
{
    public function next(): string
    {
        $prefix = (string) config('transactions.prefix', 'TXN');
        $pad = (int) config('transactions.pad', 6);

        $next = (int) DB::table('transaction_counters')
            ->where('id', 1)
            ->lockForUpdate()
            ->value('value');

        $next++;

        DB::table('transaction_counters')->where('id', 1)->update(['value' => $next]);

        return $prefix.'-'.str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
    }
}
