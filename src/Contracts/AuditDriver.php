<?php

namespace Greensight\LaravelAuditing\Contracts;

interface AuditDriver
{
    /**
     * Perform an audit.
     *
     * @param \Greensight\LaravelAuditing\Contracts\Auditable $model
     *
     * @return \Greensight\LaravelAuditing\Contracts\Audit
     */
    public function audit(Auditable $model): Audit;

    /**
     * Remove older audits that go over the threshold.
     *
     * @param \Greensight\LaravelAuditing\Contracts\Auditable $model
     *
     * @return bool
     */
    public function prune(Auditable $model): bool;
}
