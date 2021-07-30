<?php

namespace Greensight\LaravelAuditing\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\UuidInterface;

interface TransactionAttributes
{
    /**
     * Возвращает значение атрибута, если он задан и транзакция активна.
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null): mixed;

    /**
     * Устанавливает значение атрибута, если транзакция активна.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute(string $name, mixed $value): void;

    /**
     * Возвращает истину, если транзакция активна.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Возвращает уникальный идентификатор транзакции. Если транзакция неактивна, каждый
     * вызов будет возвращать новый идентификатор.
     *
     * @return UuidInterface
     */
    public function uid(): UuidInterface;

    /**
     * Возвращает время начала транзакции. Если транзакция неактивна, будет возвращено
     * текущее время.
     *
     * @return CarbonInterface
     */
    public function timestamp(): CarbonInterface;

    /**
     * Возвращает модель корневой сущности, если она была зарегистрирована для текущей
     * транзакции.
     *
     * @return Model|null
     */
    public function rootEntity(): ?Model;

    /**
     * Устанавливает для текущей транзакции модель корневой сущности.
     *
     * @param Model|null $model
     */
    public function setRootEntity(?Model $model): void;
}