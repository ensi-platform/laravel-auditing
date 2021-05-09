<?php

namespace Ensi\LaravelEnsiAudit\Tests;

use Orchestra\Testbench\TestCase;
use Ensi\LaravelEnsiAudit\EnsiAuditServiceProvider;
use Ensi\LaravelEnsiAudit\Resolvers\IpAddressResolver;
use Ensi\LaravelEnsiAudit\Resolvers\UrlResolver;
use Ensi\LaravelEnsiAudit\Resolvers\UserAgentResolver;
use Ensi\LaravelEnsiAudit\Resolvers\UserResolver;

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
        $app['config']->set('ensi-audit.drivers.database.connection', 'testing');
        $app['config']->set('ensi-audit.user.morph_prefix', 'user');
        $app['config']->set('ensi-audit.user.guards', [
            'web',
            'api',
        ]);
        $app['config']->set('ensi-audit.resolver.user', UserResolver::class);
        $app['config']->set('ensi-audit.resolver.url', UrlResolver::class);
        $app['config']->set('ensi-audit.resolver.ip_address', IpAddressResolver::class);
        $app['config']->set('ensi-audit.resolver.user_agent', UserAgentResolver::class);
        $app['config']->set('ensi-audit.console', true);
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
            EnsiAuditServiceProvider::class,
        ];
    }
}
