<?php

namespace Greensight\LaravelAuditing\Events;

use Greensight\LaravelAuditing\Contracts\Auditable;
use Greensight\LaravelAuditing\Contracts\AuditDriver;

class Auditing
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
     * Create a new Auditing event instance.
     *
     * @param \Greensight\LaravelAuditing\Contracts\Auditable   $model
     * @param \Greensight\LaravelAuditing\Contracts\AuditDriver $driver
     */
    public function __construct(Auditable $model, AuditDriver $driver)
    {
        $this->model = $model;
        $this->driver = $driver;
    }
}
