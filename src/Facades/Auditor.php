<?php

namespace Ensi\LaravelAuditing\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ensi\LaravelAuditing\Contracts\AuditDriver auditDriver(\Ensi\LaravelAuditing\Contracts\Auditable $model);
 * @method static void execute(\Ensi\LaravelAuditing\Contracts\Auditable $model);
 */
class Auditor extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return \Ensi\LaravelAuditing\Contracts\Auditor::class;
    }
}
