<?php

namespace Ensi\LaravelAuditing\Tests\Functional;

use Ensi\LaravelAuditing\Facades\Transaction;
use Ensi\LaravelAuditing\Tests\Data\Models\Factories\ArticleFactory;
use Ensi\LaravelAuditing\Tests\TestCase;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotEquals;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

use RuntimeException;

uses(TestCase::class);

test('it handles begin transaction', function () {
    /** @var TestCase $this */

    $uidBefore = Transaction::uid();
    DB::transaction(function () use ($uidBefore) {
        assertTrue(Transaction::isActive());
        assertNotEquals($uidBefore, Transaction::uid());
        assertEquals(Transaction::uid(), Transaction::uid());
    });
});

test('it handles commit', function () {
    /** @var TestCase $this */

    $uid = DB::transaction(function () {
        return Transaction::uid();
    });

    assertNotEquals($uid, Transaction::uid());
    assertFalse(Transaction::isActive());
});

test('it handles rollback', function () {
    /** @var TestCase $this */

    $uid = null;

    try {
        DB::transaction(function () use (&$uid) {
            $uid = Transaction::uid();

            throw new RuntimeException('Failed');
        });
    } catch (RuntimeException) {
    }

    assertNotEquals($uid, Transaction::uid());
    assertFalse(Transaction::isActive());
});


test('it ignores nested save points', function () {
    /** @var TestCase $this */

    DB::transaction(function () {
        $uid = Transaction::uid();
        $timestamp = Transaction::timestamp();

        DB::transaction(function () use ($uid, $timestamp) {
            assertEquals($uid, Transaction::uid());
            assertEquals($timestamp, Transaction::timestamp());
        });
    });
});


test('it remembers root entity', function () {
    /** @var TestCase $this */

    DB::transaction(function () {
        $article = ArticleFactory::new()->create();
        Transaction::setRootEntity($article);

        assertSame($article, Transaction::rootEntity());
    });

    assertNull(Transaction::rootEntity());
});
