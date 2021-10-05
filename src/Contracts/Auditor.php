<?php

namespace Ensi\LaravelAuditing\Contracts;

interface Auditor
{
    /**
     * Get an audit driver instance.
     *
     * @param \Ensi\LaravelAuditing\Contracts\Auditable $model
     *
     * @return AuditDriver
     */
    public function auditDriver(Auditable $model): AuditDriver;

    /**
     * Perform an audit.
     *
     * @param \Ensi\LaravelAuditing\Contracts\Auditable $model
     *
     * @return void
     */
    public function execute(Auditable $model);
}
