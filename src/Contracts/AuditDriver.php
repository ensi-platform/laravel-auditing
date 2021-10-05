<?php

namespace Ensi\LaravelAuditing\Contracts;

interface AuditDriver
{
    /**
     * Perform an audit.
     *
     * @param \Ensi\LaravelAuditing\Contracts\Auditable $model
     *
     * @return \Ensi\LaravelAuditing\Contracts\Audit
     */
    public function audit(Auditable $model): Audit;

    /**
     * Remove older audits that go over the threshold.
     *
     * @param \Ensi\LaravelAuditing\Contracts\Auditable $model
     *
     * @return bool
     */
    public function prune(Auditable $model): bool;
}
