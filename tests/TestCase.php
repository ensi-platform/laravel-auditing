<?php

namespace Ensi\LaravelAuditing\Tests;

use Ensi\LaravelAuditing\LaravelAuditingServiceProvider;
use Ensi\LaravelAuditing\Resolvers\IpAddressResolver;
use Ensi\LaravelAuditing\Resolvers\UrlResolver;
use Ensi\LaravelAuditing\Resolvers\UserAgentResolver;
use Ensi\LaravelAuditing\Resolvers\UserResolver;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelAuditingServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.guards.api', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        // Audit
        $app['config']->set('laravel-auditing.drivers.database.connection', 'testing');
        $app['config']->set('laravel-auditing.user.morph_prefix', 'user');
        $app['config']->set('laravel-auditing.user.guards', [
            'web',
            'api',
        ]);
        $app['config']->set('laravel-auditing.resolver.user', UserResolver::class);
        $app['config']->set('laravel-auditing.resolver.url', UrlResolver::class);
        $app['config']->set('laravel-auditing.resolver.ip_address', IpAddressResolver::class);
        $app['config']->set('laravel-auditing.resolver.user_agent', UserAgentResolver::class);
        $app['config']->set('laravel-auditing.console', true);
        $app['config']->set('laravel-auditing.empty_values', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/Data/database/migrations');
        $this->artisan('migrate')->run();
    }
}
