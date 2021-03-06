<?php

namespace Ensi\LaravelEnsiAudit\Transactions;

use Illuminate\Database\DatabaseTransactionRecord;

class ExtendedTransactionRecord extends DatabaseTransactionRecord
{
    protected $attributes = [];

    public function __construct($connection, $level, array $attributes = [])
    {
        parent::__construct($connection, $level);

        $this->attributes = $attributes;
    }

    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }
}
