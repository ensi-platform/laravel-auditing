<?php

namespace Ensi\LaravelAuditing\Tests\Unit;

use Ensi\LaravelAuditing\Tests\Unit\TestCases\TransactionRegistryTestCase;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotSame;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

uses(TransactionRegistryTestCase::class);

test('it remembers attributes for connection', function () {
    /** @var TransactionRegistryTestCase $this */

    assertSame($this->testing->attributes(), $this->testing->attributes());
    assertNotSame($this->testing->attributes(), $this->testing->attributes('other'));
});

test('it processes begin transaction', function () {
    /** @var TransactionRegistryTestCase $this */

    $this->testing->onBegin(new TransactionBeginning($this->mockConnection));

    assertTrue($this->testing->attributes()->isActive());
});

test('it ignores begun transaction', function () {
    /** @var TransactionRegistryTestCase $this */

    $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
    $uid = $this->testing->uid();

    $this->testing->onBegin(new TransactionBeginning($this->mockConnection));

    assertEquals($uid, $this->testing->uid());
});

test('it processes commit transaction', function () {
    /** @var TransactionRegistryTestCase $this */

    $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
    $this->mockConnection->shouldReceive('transactionLevel')->andReturn(0);

    $this->testing->onCommit(new TransactionCommitted($this->mockConnection));

    assertFalse($this->testing->attributes()->isActive());
});

test('it ignores commit save point', function () {
    /** @var TransactionRegistryTestCase $this */

    $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
    $this->mockConnection->shouldReceive('transactionLevel')->andReturn(1);

    $this->testing->onCommit(new TransactionCommitted($this->mockConnection));

    assertTrue($this->testing->attributes()->isActive());
});

test('it processes rollback transaction', function () {
    /** @var TransactionRegistryTestCase $this */

    $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
    $this->mockConnection->shouldReceive('transactionLevel')->andReturn(0);

    $this->testing->onRollback(new TransactionRolledBack($this->mockConnection));

    assertFalse($this->testing->attributes()->isActive());
});

test('it ignores rollback save point', function () {
    /** @var TransactionRegistryTestCase $this */

    $this->testing->onBegin(new TransactionBeginning($this->mockConnection));
    $this->mockConnection->shouldReceive('transactionLevel')->andReturn(1);

    $this->testing->onRollback(new TransactionRolledBack($this->mockConnection));

    assertTrue($this->testing->attributes()->isActive());
});
