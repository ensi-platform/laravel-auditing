<?php

namespace Greensight\LaravelAuditing\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Greensight\LaravelAuditing\Contracts\AuditDriver auditDriver(\Greensight\LaravelAuditing\Contracts\Auditable $model);
 * @method static void execute(\Greensight\LaravelAuditing\Contracts\Auditable $model);
 */
class Auditor extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return \Greensight\LaravelAuditing\Contracts\Auditor::class;
    }
}
