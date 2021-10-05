<?php

namespace Ensi\LaravelAuditing\Events;

use Ensi\LaravelAuditing\Contracts\Audit;
use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\Contracts\AuditDriver;

class Audited
{
    /**
     * The Auditable model.
     *
     * @var \Ensi\LaravelAuditing\Contracts\Auditable
     */
    public $model;

    /**
     * Audit driver.
     *
     * @var \Ensi\LaravelAuditing\Contracts\AuditDriver
     */
    public $driver;

    /**
     * The Audit model.
     *
     * @var \Ensi\LaravelAuditing\Contracts\Audit|null
     */
    public $audit;

    /**
     * Create a new Audited event instance.
     *
     * @param \Ensi\LaravelAuditing\Contracts\Auditable   $model
     * @param \Ensi\LaravelAuditing\Contracts\AuditDriver $driver
     * @param \Ensi\LaravelAuditing\Contracts\Audit       $audit
     */
    public function __construct(Auditable $model, AuditDriver $driver, Audit $audit = null)
    {
        $this->model = $model;
        $this->driver = $driver;
        $this->audit = $audit;
    }
}
