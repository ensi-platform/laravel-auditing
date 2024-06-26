<?php

namespace Ensi\LaravelAuditing;

use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\Contracts\AuditDriver;
use Ensi\LaravelAuditing\Drivers\Database;
use Ensi\LaravelAuditing\Events\Audited;
use Ensi\LaravelAuditing\Events\Auditing;
use Ensi\LaravelAuditing\Exceptions\AuditingException;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class Auditor extends Manager implements Contracts\Auditor
{
    /**
     * {@inheritdoc}
     */
    public function getDefaultDriver()
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (InvalidArgumentException $exception) {
            if (class_exists($driver)) {
                return $this->container->make($driver);
            }

            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function auditDriver(Auditable $model): AuditDriver
    {
        $driver = $this->driver($model->getAuditDriver());

        if (!$driver instanceof AuditDriver) {
            throw new AuditingException('The driver must implement the AuditDriver contract');
        }

        return $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Auditable $model)
    {
        if (!$model->readyForAuditing()) {
            return;
        }

        $driver = $this->auditDriver($model);

        if (!$this->fireAuditingEvent($model, $driver)) {
            return;
        }

        $audit = $driver->audit($model);
        $driver->prune($model);

        $this->container->make('events')->dispatch(
            new Audited($model, $driver, $audit)
        );
    }

    /**
     * Create an instance of the Database audit driver.
     *
     * @return \Ensi\LaravelAuditing\Drivers\Database
     */
    protected function createDatabaseDriver(): Database
    {
        return $this->container->make(Database::class);
    }

    /**
     * Fire the Auditing event.
     *
     * @param \Ensi\LaravelAuditing\Contracts\Auditable   $model
     * @param \Ensi\LaravelAuditing\Contracts\AuditDriver $driver
     *
     * @return bool
     */
    protected function fireAuditingEvent(Auditable $model, AuditDriver $driver): bool
    {
        return $this->container->make('events')->until(
            new Auditing($model, $driver)
        ) !== false;
    }
}
