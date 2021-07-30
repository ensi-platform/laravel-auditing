<?php

namespace Greensight\LaravelAuditing\Events;

use Greensight\LaravelAuditing\Contracts\Audit;
use Greensight\LaravelAuditing\Contracts\Auditable;
use Greensight\LaravelAuditing\Contracts\AuditDriver;

class Audited
{
    /**
     * The Auditable model.
     *
     * @var \Greensight\LaravelAuditing\Contracts\Auditable
     */
    public $model;

    /**
     * Audit driver.
     *
     * @var \Greensight\LaravelAuditing\Contracts\AuditDriver
     */
    public $driver;

    /**
     * The Audit model.
     *
     * @var \Greensight\LaravelAuditing\Contracts\Audit|null
     */
    public $audit;

    /**
     * Create a new Audited event instance.
     *
     * @param \Greensight\LaravelAuditing\Contracts\Auditable   $model
     * @param \Greensight\LaravelAuditing\Contracts\AuditDriver $driver
     * @param \Greensight\LaravelAuditing\Contracts\Audit       $audit
     */
    public function __construct(Auditable $model, AuditDriver $driver, Audit $audit = null)
    {
        $this->model = $model;
        $this->driver = $driver;
        $this->audit = $audit;
    }
}
