<?php

namespace Ensi\LaravelAuditing\Console;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'auditing:install';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Install all of the Auditing resources';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->comment('Publishing Auditing Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'config']);

        $this->comment('Publishing Auditing Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'migrations']);

        $this->registerAuditingServiceProvider();

        $this->info('Auditing installed successfully.');
    }

    /**
     * Register the Auditing service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerAuditingServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', Container::getInstance()->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, 'Ensi\\LaravelAuditing\\LaravelAuditingServiceProvider::class')) {
            return;
        }

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        Ensi\LaravelAuditing\LaravelAuditingServiceProvider::class,".PHP_EOL,
            $appConfig
        ));
    }
}
