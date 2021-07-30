<?php

namespace Greensight\LaravelAuditing\Contracts;

interface Auditor
{
    /**
     * Get an audit driver instance.
     *
     * @param \Greensight\LaravelAuditing\Contracts\Auditable $model
     *
     * @return AuditDriver
     */
    public function auditDriver(Auditable $model): AuditDriver;

    /**
     * Perform an audit.
     *
     * @param \Greensight\LaravelAuditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function execute(Auditable $model);
}
