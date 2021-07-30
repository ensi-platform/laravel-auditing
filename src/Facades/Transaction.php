<?php

namespace Greensight\LaravelAuditing\Facades;

use Greensight\LaravelAuditing\Transactions\TransactionRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * Transaction обеспечивает доступ к атрибутам текущей транзакции.
 *
 * @method static \Greensight\LaravelAuditing\Contracts\TransactionAttributes attributes(string $connectionName=null)
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
