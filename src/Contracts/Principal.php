<?php

namespace Ensi\LaravelAuditing\Contracts;

/**
 * Principal интерфейс субъекта доступа.
 */
interface Principal
{
    /**
     * Возвращает идентификатор субъекта доступа.
     * Например, это может быть задание на импорт.
     *
     * @return int
     */
    public function getAuthIdentifier(): int;

    /**
     * Возвращает имя класса для полиморфной связи.
     * @return string
     */
    public function getMorphClass();

    /**
     * Возвращает наименование субъекта доступа.
     * @return string
     */
    public function getName(): string;

    /**
     * Возвращает идентификатор пользователя, если есть.
     *
     * @return mixed
     */
    public function getUserIdentifier();
}
