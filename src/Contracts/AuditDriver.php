<?php

namespace Ensi\LaravelAuditing\Contracts;

interface AuditDriver
{
    /**
     * Perform an audit.
     *
     * @param Auditable $model
     *
     * @return Audit
     */
    public function audit(Auditable $model): Audit;

    /**
     * Remove older audits that go over the threshold.
     *
     * @param Auditable $model
     *
     * @return bool
     */
    public function prune(Auditable $model): bool;
}
