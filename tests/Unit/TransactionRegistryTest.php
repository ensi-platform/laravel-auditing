<?php

namespace Ensi\LaravelAuditing\Tests\Unit;

use Ensi\LaravelAuditing\Tests\AuditingTestCase;
use Ensi\LaravelAuditing\Transactions\TransactionRegistry;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Mockery;

class TransactionRegistryTest extends AuditingTestCase
{
    const DEFAULT_CONNECTION_NAME = 'default';

    private $mockConnection;
    private $testing;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockConnection = $this->mockConnection(self::DEFAULT_CONNECTION_NAME);
        $this->testing = new TransactionRegistry(self::DEFAULT_CONNECTION_NAME);
    }

    /**
     * @test
     */
    public function itRemembersAttributesForConnection(): void
    {
        $this->assertSame($this->testing->attributes(), $this->testing->attributes());
        $this->assertNotSame($this->testing->attributes(), $this->testing->attributes('other'));
    }

    /**
     * @test
     */
    public function itProcessesBeginTransaction(): void
    {
        $this->testing->onBegin(new TransactionBeginning($this->mockConnection));

        $this->assertTrue($this->testing->attributes()->isActive());
    }

    /**
     * @test
     */
    public function itIgnoresBegunTransaction(): void
    {
        $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
        $uid = $this->testing->uid();

        $this->testing->onBegin(new TransactionBeginning($this->mockConnection));

        $this->assertEquals($uid, $this->testing->uid());
    }

    /**
     * @test
     */
    public function itProcessesCommitTransaction(): void
    {
        $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
        $this->mockConnection->shouldReceive('transactionLevel')->andReturn(0);

        $this->testing->onCommit(new TransactionCommitted($this->mockConnection));

        $this->assertFalse($this->testing->attributes()->isActive());
    }

    /**
     * @test
     */
    public function itIgnoresCommitSavePoint(): void
    {
        $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
        $this->mockConnection->shouldReceive('transactionLevel')->andReturn(1);

        $this->testing->onCommit(new TransactionCommitted($this->mockConnection));

        $this->assertTrue($this->testing->attributes()->isActive());
    }

    /**
     * @test
     */
    public function itProcessesRollbackTransaction(): void
    {
        $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
        $this->mockConnection->shouldReceive('transactionLevel')->andReturn(0);

        $this->testing->onRollback(new TransactionRolledBack($this->mockConnection));

        $this->assertFalse($this->testing->attributes()->isActive());
    }

    /**
     * @test
     */
    public function itIgnoresRollbackSavePoint(): void
    {
        $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
        $this->mockConnection->shouldReceive('transactionLevel')->andReturn(1);

        $this->testing->onRollback(new TransactionRolledBack($this->mockConnection));

        $this->assertTrue($this->testing->attributes()->isActive());
    }

    private function mockConnection(string $name): Mockery\MockInterface|Connection
    {
        $mockConnection = Mockery::mock(Connection::class);
        $mockConnection->shouldReceive('getName')->andReturn($name);

        return $mockConnection;
    }
}
