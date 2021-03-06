<?php

namespace Ensi\LaravelEnsiAudit\Transactions;

use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Optional;
use Illuminate\Support\Str;
use Ramsey\Uuid\UuidInterface;

class ExtendedTransactionManager extends DatabaseTransactionsManager
{
    const ATTRIBUTE_UID = 'uid';
    const ATTRIBUTE_TIMESTAMP = 'timestamp';
    const ATTRIBUTE_ROOT_ENTITY = 'root_entity';

    public function begin($connection, $level)
    {
        $attributes = [
            static::ATTRIBUTE_UID => Str::uuid(),
            static::ATTRIBUTE_TIMESTAMP => Date::now(),
        ];

        $this->transactions->push(
            new ExtendedTransactionRecord($connection, $level, $attributes)
        );
    }

    public function uid(): UuidInterface
    {
        return $this->current()->getAttribute(static::ATTRIBUTE_UID);
    }

    public function timestamp(): CarbonInterface
    {
        return $this->current()->getAttribute(static::ATTRIBUTE_TIMESTAMP) ?? now();
    }

    public function rootEntity(): ?Model
    {
        return $this->current()->getAttribute(static::ATTRIBUTE_ROOT_ENTITY);
    }

    public function setRootEntity(Model $model): void
    {
        $this->current()->setAttribute(static::ATTRIBUTE_ROOT_ENTITY, $model);
    }

    public function isActive(): bool
    {
        return $this->transactions->isNotEmpty();
    }

    /**
     * @return Optional|ExtendedTransactionRecord
     */
    protected function current(): Optional
    {
        return new Optional($this->transactions->last());
    }
}
