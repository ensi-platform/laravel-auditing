<?php

namespace Ensi\LaravelEnsiAudit;

use Ensi\LaravelEnsiAudit\Console\AuditDriverCommand;
use Ensi\LaravelEnsiAudit\Console\InstallCommand;
use Ensi\LaravelEnsiAudit\Contracts\Auditor;
use Ensi\LaravelEnsiAudit\Drivers\Database;
use Ensi\LaravelEnsiAudit\Facades\Subject;
use Ensi\LaravelEnsiAudit\Facades\Transaction;
use Ensi\LaravelEnsiAudit\Resolvers\SubjectManager;
use Ensi\LaravelEnsiAudit\Transactions\ExtendedTransactionManager;
use Illuminate\Support\ServiceProvider;

class EnsiAuditServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
        $this->mergeConfigFrom(__DIR__.'/../config/ensi-audit.php', 'ensi-audit');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            AuditDriverCommand::class,
            InstallCommand::class,
        ]);

        $this->app->singleton('db.transactions', function () {
            return new ExtendedTransactionManager();
        });

        $this->app->singleton(Database::class, function () {
            return new Database($this->app['db.transactions']);
        });

        $this->app->singleton(SubjectManager::class);

        $this->app->singleton(Auditor::class, function ($app) {
            return new \Ensi\LaravelEnsiAudit\Auditor($app);
        });

        $this->app->singleton(Subject::class);
        $this->app->singleton(Transaction::class);
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ensi-audit.php' => base_path('config/ensi-audit.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/audits.stub' => database_path(
                    sprintf('migrations/%s_create_audits_table.php', date('Y_m_d_His'))
                ),
            ], 'migrations');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return [
            Auditor::class,
            Subject::class,
            Transaction::class,
        ];
    }
}