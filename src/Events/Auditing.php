<?php

namespace Ensi\LaravelAuditing\Events;

use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\Contracts\AuditDriver;

class Auditing
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
     * Create a new Auditing event instance.
     *
     * @param \Ensi\LaravelAuditing\Contracts\Auditable   $model
     * @param \Ensi\LaravelAuditing\Contracts\AuditDriver $driver
     */
    public function __construct(Auditable $model, AuditDriver $driver)
    {
        $this->model = $model;
        $this->driver = $driver;
    }
}
