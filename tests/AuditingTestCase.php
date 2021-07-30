<?php

namespace Greensight\LaravelAuditing\Tests;

use Greensight\LaravelAuditing\LaravelAuditingServiceProvider;
use Greensight\LaravelAuditing\Facades\Subject;
use Greensight\LaravelAuditing\Resolvers\IpAddressResolver;
use Greensight\LaravelAuditing\Resolvers\UrlResolver;
use Greensight\LaravelAuditing\Resolvers\UserAgentResolver;
use Orchestra\Testbench\TestCase;

class AuditingTestCase extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentSetUp($app)
    {
        // Database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Audit
        $app['config']->set('laravel-auditing.drivers.database.connection', 'testing');
        $app['config']->set('laravel-auditing.user.morph_prefix', 'user');
        $app['config']->set('laravel-auditing.user.guards', [
            'web',
            'api',
        ]);
        $app['config']->set('laravel-auditing.resolver.user', Subject::class);
        $app['config']->set('laravel-auditing.resolver.url', UrlResolver::class);
        $app['config']->set('laravel-auditing.resolver.ip_address', IpAddressResolver::class);
        $app['config']->set('laravel-auditing.resolver.user_agent', UserAgentResolver::class);
        $app['config']->set('laravel-auditing.console', true);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelAuditingServiceProvider::class,
        ];
    }
}
