<?php

namespace Ensi\LaravelAuditing\Tests\Functional;

use Ensi\LaravelAuditing\Database\Factories\ArticleFactory;
use Ensi\LaravelAuditing\Facades\Transaction;
use Ensi\LaravelAuditing\Tests\AuditingTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransactionTest extends AuditingTestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function itHandlesBeginTransaction(): void
    {
        $uidBefore = Transaction::uid();
        DB::transaction(function () use ($uidBefore) {
            $this->assertTrue(Transaction::isActive());
            $this->assertNotEquals($uidBefore, Transaction::uid());
            $this->assertEquals(Transaction::uid(), Transaction::uid());
        });
    }

    /**
     * @test
     */
    public function itHandlesCommit(): void
    {
        $uid = DB::transaction(function () {
            return Transaction::uid();
        });

        $this->assertNotEquals($uid, Transaction::uid());
        $this->assertFalse(Transaction::isActive());
    }

    /**
     * @test
     */
    public function itHandlesRollback(): void
    {
        $uid = null;
        try {
            DB::transaction(function () use (&$uid) {
                $uid = Transaction::uid();
                throw new RuntimeException('Failed');
            });
        } catch (RuntimeException) {
        }

        $this->assertNotEquals($uid, Transaction::uid());
        $this->assertFalse(Transaction::isActive());
    }

    /**
     * @test
     */
    public function itIgnoresNestedSavePoints(): void
    {
        DB::transaction(function () {
            $uid = Transaction::uid();
            $timestamp = Transaction::timestamp();

            DB::transaction(function () use ($uid, $timestamp) {
                $this->assertEquals($uid, Transaction::uid());
                $this->assertEquals($timestamp, Transaction::timestamp());
            });
        });
    }

    /**
     * @test
     */
    public function itRemembersRootEntity(): void
    {
        DB::transaction(function () {
            $article = ArticleFactory::new()->create();
            Transaction::setRootEntity($article);

            $this->assertSame($article, Transaction::rootEntity());
        });

        $this->assertNull(Transaction::rootEntity());
    }
}