<?php

namespace Ensi\LaravelEnsiAudit\Contracts;

interface Auditor
{
    /**
     * Get an audit driver instance.
     *
     * @param \Ensi\LaravelEnsiAudit\Contracts\Auditable $model
     *
     * @return AuditDriver
     */
    public function auditDriver(Auditable $model): AuditDriver;

    /**
     * Perform an audit.
     *
     * @param \Ensi\LaravelEnsiAudit\Contracts\Auditable $model
     *
     * @return void
     */
    public function execute(Auditable $model);
}
