<?php

namespace Ensi\LaravelEnsiAudit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ensi\LaravelEnsiAudit\Contracts\AuditDriver auditDriver(\Ensi\LaravelEnsiAudit\Contracts\Auditable $model);
 * @method static void execute(\Ensi\LaravelEnsiAudit\Contracts\Auditable $model);
 */
class Auditor extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return \Ensi\LaravelEnsiAudit\Contracts\Auditor::class;
    }
}
