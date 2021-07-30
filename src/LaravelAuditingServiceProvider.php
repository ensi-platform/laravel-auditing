<?php

namespace Greensight\LaravelAuditing;

use Greensight\LaravelAuditing\Console\AuditDriverCommand;
use Greensight\LaravelAuditing\Console\InstallCommand;
use Greensight\LaravelAuditing\Contracts\Auditor;
use Greensight\LaravelAuditing\Drivers\Database;
use Greensight\LaravelAuditing\Facades\Subject;
use Greensight\LaravelAuditing\Facades\Transaction;
use Greensight\LaravelAuditing\Resolvers\SubjectManager;
use Greensight\LaravelAuditing\Transactions\TransactionRegistry;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class LaravelAuditingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
        $this->registerListeners();

        $this->mergeConfigFrom(__DIR__.'/../config/laravel-auditing.php', 'laravel-auditing');
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

        $this->app->singleton(TransactionRegistry::class, function (Application $app) {
            return new TransactionRegistry($app['config']['database.default']);
        });

        $this->app->singleton(Database::class, function (Application $app) {
            return new Database($app->make(TransactionRegistry::class));
        });


        $this->app->singleton(Auditor::class, function (Application $app) {
            return new \Greensight\LaravelAuditing\Auditor($app);
        });

        $this->app->singleton(SubjectManager::class);
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
                __DIR__.'/../config/laravel-auditing.php' => base_path('config/laravel-auditing.php'),
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

    private function registerListeners(): void
    {
        Event::listen(TransactionBeginning::class, [TransactionRegistry::class, 'onBegin']);
        Event::listen(TransactionCommitted::class, [TransactionRegistry::class, 'onCommit']);
        Event::listen(TransactionRolledBack::class, [TransactionRegistry::class, 'onRollback']);
    }
}