<?php

namespace Ensi\LaravelAuditing\Tests\Unit\TestCases;

use Ensi\LaravelAuditing\Tests\TestCase;
use Ensi\LaravelAuditing\Transactions\TransactionRegistry;
use Illuminate\Database\Connection;
use Mockery\MockInterface;

class TransactionRegistryTestCase extends TestCase
{
    public const DEFAULT_CONNECTION_NAME = 'default';

    protected MockInterface|Connection $mockConnection;
    protected TransactionRegistry $testing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConnection = $this->mockConnection();
        $this->testing = new TransactionRegistry(self::DEFAULT_CONNECTION_NAME);
    }

    protected function mockConnection(): MockInterface|Connection
    {
        $mockConnection = $this->mock(Connection::class);
        $mockConnection->shouldReceive('getName')->andReturn(self::DEFAULT_CONNECTION_NAME);

        return $mockConnection;
    }
}
