<?php

namespace Greensight\LaravelAuditing\Tests\Functional;

use Carbon\Carbon;
use Greensight\LaravelAuditing\Database\Factories\ApiModelFactory;
use Greensight\LaravelAuditing\Database\Factories\ArticleFactory;
use Greensight\LaravelAuditing\Events\Auditing;
use Greensight\LaravelAuditing\Exceptions\AuditingException;
use Greensight\LaravelAuditing\Facades\Transaction;
use Greensight\LaravelAuditing\Models\Audit;
use Greensight\LaravelAuditing\Tests\AuditingTestCase;
use Greensight\LaravelAuditing\Tests\Models\Article;
use Greensight\LaravelAuditing\Tests\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Assert;
use InvalidArgumentException;

class AuditingTest extends AuditingTestCase
{
    /**
     * @test
     */
    public function itWillNotAuditModelsWhenRunningFromTheConsole()
    {
        $this->app['config']->set('laravel-auditing.console', false);

        User::factory()->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(0, Audit::query()->count());
    }

    /**
     * @test
     */
    public function itWillAuditModelsWhenRunningFromTheConsole()
    {
        $this->app['config']->set('laravel-auditing.console', true);

        User::factory()->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, Audit::query()->count());
    }

    /**
     * @test
     */
    public function itWillAlwaysAuditModelsWhenNotRunningFromTheConsole()
    {
        App::shouldReceive('runningInConsole')
            ->andReturn(false);

        $this->app['config']->set('laravel-auditing.console', false);

        User::factory()->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, Audit::query()->count());
    }

    /**
     * @test
     */
    public function itWillNotAuditTheRetrievingEvent()
    {
        $this->app['config']->set('laravel-auditing.console', true);

        User::factory()->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, Audit::query()->count());

        User::first();

        $this->assertSame(1, Audit::query()->count());
        $this->assertSame(1, User::query()->count());
    }

    /**
     * @test
     */
    public function itWillAuditTheRetrievingEvent()
    {
        $this->app['config']->set('laravel-auditing.console', true);
        $this->app['config']->set('laravel-auditing.events', [
            'created',
            'retrieved',
        ]);

        User::factory()->create();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, Audit::query()->count());

        User::first();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(2, Audit::query()->count());
    }

    /**
     * @test
     */
    public function itWillAuditTheRetrievedEvent()
    {
        $this->app['config']->set('laravel-auditing.events', [
            'retrieved',
        ]);

        Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        Article::first();

        $audit = Audit::first();

        $this->assertEmpty($audit->old_values);

        $this->assertEmpty($audit->new_values);
    }

    /**
     * @test
     */
    public function itWillAuditTheCreatedEvent()
    {
        $this->app['config']->set('laravel-auditing.events', [
            'created',
        ]);

        Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $audit = Audit::first();

        $this->assertEmpty($audit->old_values);

        Assert::assertArraySubset([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function itWillAuditTheUpdatedEvent()
    {
        $this->app['config']->set('laravel-auditing.events', [
            'updated',
        ]);

        $article = Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $now = Carbon::now();

        $article->update([
            'content'      => 'First step: install the laravel-auditing package.',
            'published_at' => $now,
            'reviewed'     => 1,
        ]);

        $audit = Audit::first();

        Assert::assertArraySubset([
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ], $audit->old_values, true);

        Assert::assertArraySubset([
            'content'      => 'First step: install the laravel-auditing package.',
            'published_at' => $now->toDateTimeString(),
            'reviewed'     => 1,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function itWillAuditTheDeletedEvent()
    {
        $this->app['config']->set('laravel-auditing.events', [
            'deleted',
        ]);

        $article = Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $article->delete();

        $audit = Audit::first();

        Assert::assertArraySubset([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ], $audit->old_values, true);

        $this->assertEmpty($audit->new_values);
    }

    /**
     * @test
     */
    public function itWillAuditTheRestoredEvent()
    {
        $this->app['config']->set('laravel-auditing.events', [
            'restored',
        ]);

        $article = Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $article->delete();
        $article->restore();

        $audit = Audit::first();

        $this->assertEmpty($audit->old_values);

        Assert::assertArraySubset([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function itWillKeepAllAudits()
    {
        $this->app['config']->set('laravel-auditing.threshold', 0);
        $this->app['config']->set('laravel-auditing.events', [
            'updated',
        ]);

        $article = Article::factory()->create([
            'reviewed' => 1,
        ]);

        foreach (range(0, 99) as $count) {
            $article->update([
                'reviewed' => ($count % 2),
            ]);
        }

        $this->assertSame(100, $article->audits()->count());
    }

    /**
     * @test
     */
    public function itWillRemoveOlderAuditsAboveTheThreshold()
    {
        $this->app['config']->set('laravel-auditing.threshold', 10);
        $this->app['config']->set('laravel-auditing.events', [
            'updated',
        ]);

        $article = Article::factory()->create([
            'reviewed' => 1,
        ]);

        foreach (range(0, 99) as $count) {
            $article->update([
                'reviewed' => ($count % 2),
            ]);
        }

        $this->assertSame(10, $article->audits()->count());
    }

    /**
     * @test
     */
    public function itWillNotAuditDueToUnsupportedDriver()
    {
        $this->app['config']->set('laravel-auditing.driver', 'foo');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [foo] not supported.');

        Article::factory()->create();
    }

    /**
     * @test
     */
    public function itWillNotAuditDueToClassWithoutDriverInterface()
    {
        // We just pass a FQCN that does not implement the AuditDriver interface
        $this->app['config']->set('laravel-auditing.driver', self::class);

        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('The driver must implement the AuditDriver contract');

        Article::factory()->create();
    }

    /**
     * @test
     */
    public function itWillAuditUsingTheDefaultDriver()
    {
        $this->app['config']->set('laravel-auditing.driver', null);

        Article::factory()->create([
            'title'        => 'How To Audit Using The Fallback Driver',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ]);

        $audit = Audit::first();

        $this->assertEmpty($audit->old_values);

        Assert::assertArraySubset([
            'title'        => 'How To Audit Using The Fallback Driver',
            'content'      => 'N/A',
            'published_at' => null,
            'reviewed'     => 0,
        ], $audit->new_values, true);
    }

    /**
     * @test
     */
    public function itWillCancelTheAuditFromAnEventListener()
    {
        Event::listen(Auditing::class, function () {
            return false;
        });

        Article::factory()->create();

        $this->assertNull(Audit::first());
    }

    /**
     * @test
     */
    public function itDisablesAndEnablesAuditingBackAgain()
    {
        // Auditing is enabled by default
        $this->assertFalse(Article::$auditingDisabled);

        Article::factory()->create();

        $this->assertSame(1, Article::count());
        $this->assertSame(1, Audit::count());

        // Disable Auditing
        Article::disableAuditing();
        $this->assertTrue(Article::$auditingDisabled);

        Article::factory()->create();

        $this->assertSame(2, Article::count());
        $this->assertSame(1, Audit::count());

        // Re-enable Auditing
        Article::enableAuditing();
        $this->assertFalse(Article::$auditingDisabled);

        Article::factory()->create();

        $this->assertSame(2, Audit::count());
        $this->assertSame(3, Article::count());
    }

    /**
     * @test
     */
    public function itAddsTransactionAttributesToAudit(): void
    {
        DB::transaction(function () {
            /** @var Audit $audit */
            $audit = ArticleFactory::new()->create()->audits()->first();

            $this->assertEquals($audit->transaction_uid, Transaction::uid()->toString());
            $this->assertEquals($audit->transaction_time, Transaction::timestamp());
        });
    }

    /**
     * @test
     */
    public function itAddsRootEntityToAudit(): void
    {
        /** @var Article $article */
        $article = ArticleFactory::new()->create();

        DB::transaction(function () use ($article) {
            Transaction::setRootEntity($article);

            /** @var Audit $audit */
            $audit = ApiModelFactory::new()->create()->audits()->first();

            $this->assertEquals($article->getKey(), $audit->root_entity_id);
            $this->assertEquals($article->getMorphClass(), $audit->root_entity_type);
        });
    }
}
