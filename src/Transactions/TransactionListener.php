<?php

namespace Ensi\LaravelEnsiAudit\Transactions;

use Illuminate\Database\Events\TransactionBeginning;

class TransactionListener
{
    public function onBegin(TransactionBeginning $event): void
    {
        echo "Begining";
    }
}