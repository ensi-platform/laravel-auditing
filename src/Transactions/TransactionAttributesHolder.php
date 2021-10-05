<?php

namespace Ensi\LaravelAuditing\Transactions;

use Carbon\CarbonInterface;
use Ensi\LaravelAuditing\Contracts\TransactionAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Ramsey\Uuid\UuidInterface;

class TransactionAttributesHolder implements TransactionAttributes
{
    public const ATTRIBUTE_UID = 'uid';
    public const ATTRIBUTE_TIMESTAMP = 'timestamp';
    public const ATTRIBUTE_ROOT_ENTITY = 'root_entity';

    private array $attributes = [];
    private bool $active = false;

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function setAttribute(string $name, $value): void
    {
        if ($this->isActive()) {
            $this->attributes[$name] = $value;
        }
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function uid(): UuidInterface
    {
        return $this->attributes[self::ATTRIBUTE_UID] ?? Str::uuid();
    }

    public function timestamp(): CarbonInterface
    {
        return $this->attributes[self::ATTRIBUTE_TIMESTAMP] ?? Date::now();
    }

    public function rootEntity(): ?Model
    {
        return $this->attributes[self::ATTRIBUTE_ROOT_ENTITY] ?? null;
    }

    public function setRootEntity(?Model $model): void
    {
        if ($this->isActive()) {
            $this->attributes[self::ATTRIBUTE_ROOT_ENTITY] = $model;
        }
    }

    public function begin(): void
    {
        $this->attributes = [
            static::ATTRIBUTE_UID => Str::uuid(),
            static::ATTRIBUTE_TIMESTAMP => Date::now(),
        ];
        $this->active = true;
    }

    public function commit(): void
    {
        $this->reset();
    }

    public function rollback(): void
    {
        $this->reset();
    }

    private function reset(): void
    {
        $this->attributes = [];
        $this->active = false;
    }
}