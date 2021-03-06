<?php

namespace Ensi\LaravelEnsiAudit\Contracts;

interface AuditDriver
{
    /**
     * Perform an audit.
     *
     * @param \Ensi\LaravelEnsiAudit\Contracts\Auditable $model
     *
     * @return \Ensi\LaravelEnsiAudit\Contracts\Audit
     */
    public function audit(Auditable $model): Audit;

    /**
     * Remove older audits that go over the threshold.
     *
     * @param \Ensi\LaravelEnsiAudit\Contracts\Auditable $model
     *
     * @return bool
     */
    public function prune(Auditable $model): bool;
}
