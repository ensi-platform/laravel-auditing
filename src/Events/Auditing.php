<?php

namespace Ensi\LaravelEnsiAudit\Events;

use Ensi\LaravelEnsiAudit\Contracts\Auditable;
use Ensi\LaravelEnsiAudit\Contracts\AuditDriver;

class Auditing
{
    /**
     * The Auditable model.
     *
     * @var \Ensi\LaravelEnsiAudit\Contracts\Auditable
     */
    public $model;

    /**
     * Audit driver.
     *
     * @var \Ensi\LaravelEnsiAudit\Contracts\AuditDriver
     */
    public $driver;

    /**
     * Create a new Auditing event instance.
     *
     * @param \Ensi\LaravelEnsiAudit\Contracts\Auditable   $model
     * @param \Ensi\LaravelEnsiAudit\Contracts\AuditDriver $driver
     */
    public function __construct(Auditable $model, AuditDriver $driver)
    {
        $this->model = $model;
        $this->driver = $driver;
    }
}
