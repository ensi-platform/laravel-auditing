<?php

namespace Ensi\LaravelEnsiAudit\Events;

use Ensi\LaravelEnsiAudit\Contracts\Audit;
use Ensi\LaravelEnsiAudit\Contracts\Auditable;
use Ensi\LaravelEnsiAudit\Contracts\AuditDriver;

class Audited
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
     * The Audit model.
     *
     * @var \Ensi\LaravelEnsiAudit\Contracts\Audit|null
     */
    public $audit;

    /**
     * Create a new Audited event instance.
     *
     * @param \Ensi\LaravelEnsiAudit\Contracts\Auditable   $model
     * @param \Ensi\LaravelEnsiAudit\Contracts\AuditDriver $driver
     * @param \Ensi\LaravelEnsiAudit\Contracts\Audit       $audit
     */
    public function __construct(Auditable $model, AuditDriver $driver, Audit $audit = null)
    {
        $this->model = $model;
        $this->driver = $driver;
        $this->audit = $audit;
    }
}
