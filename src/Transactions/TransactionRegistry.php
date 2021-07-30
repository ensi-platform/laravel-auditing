<?php

namespace Greensight\LaravelAuditing\Transactions;

use Carbon\CarbonInterface;
use Greensight\LaravelAuditing\Contracts\TransactionAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ConnectionEvent;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Collection;
use Ramsey\Uuid\UuidInterface;

class TransactionRegistry
{
    private Collection $holders;
    private string $defaultConnectionName;

    public function __construct(string $defaultConnectionName)
    {
        $this->defaultConnectionName = $defaultConnectionName;
        $this->holders = new Collection();
    }

    public function attributes(?string $connectionName = null): TransactionAttributes
    {
        return $this->getOrCreateHolder($connectionName ?? $this->defaultConnectionName);
    }

    public function isActive(): bool
    {
        return $this->attributes()->isActive();
    }

    public function uid(): UuidInterface
    {
        return $this->attributes()->uid();
    }

    public function timestamp(): CarbonInterface
    {
        return $this->attributes()->timestamp();
    }

    public function rootEntity(): ?Model
    {
        return $this->attributes()->rootEntity();
    }

    public function setRootEntity(?Model $model): void
    {
        $this->attributes()->setRootEntity($model);
    }

    public function onBegin(TransactionBeginning $event): void
    {
        $holder = $this->getOrCreateHolder($event->connectionName);

        if (!$holder->isActive()) {
            $holder->begin();
        }
    }

    public function onCommit(TransactionCommitted $event): void
    {
        $this->executeIfTransactionFinished($event, fn($holder) => $holder->commit());
    }

    public function onRollback(TransactionRolledBack $event): void
    {
        $this->executeIfTransactionFinished($event, fn($holder) => $holder->rollback());
    }

    private function getOrCreateHolder(string $connectionName): TransactionAttributesHolder
    {
        return $this->holders->get($connectionName, fn() => $this->createHolder($connectionName));
    }

    private function createHolder(string $connectionName): TransactionAttributesHolder
    {
        $holder = new TransactionAttributesHolder();
        $this->holders->put($connectionName, $holder);

        return $holder;
    }

    private function executeIfTransactionFinished(ConnectionEvent $event, callable $callback): void
    {
        if ($event->connection->transactionLevel() != 0) {
            return;
        }

        if ($holder = $this->holders->get($event->connectionName)) {
            $callback($holder);
        }
    }
}