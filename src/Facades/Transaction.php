<?php

namespace Ensi\LaravelEnsiAudit\Facades;

use Ensi\LaravelEnsiAudit\Transactions\TransactionRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * Transaction обеспечивает доступ к атрибутам текущей транзакции.
 *
 * @method static \Ensi\LaravelEnsiAudit\Transactions\TransactionAttributes attributes(string $connectionName=null)
 * @method static bool isActive()
 * @method static \Carbon\CarbonInterface timestamp()
 * @method static \Ramsey\Uuid\UuidInterface uid()
 * @method static \Illuminate\Database\Eloquent\Model|null rootEntity()
 * @method static void setRootEntity(\Illuminate\Database\Eloquent\Model $model)
 */
class Transaction extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TransactionRegistry::class;
    }
}
