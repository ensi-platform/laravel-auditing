<?php

namespace Ensi\LaravelEnsiAudit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Transaction обеспечивает доступ к атрибутам текущей транзакции.
 *
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
        return 'db.transactions';
    }
}
