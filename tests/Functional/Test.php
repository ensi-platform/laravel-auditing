<?php

namespace Ensi\LaravelAuditing\Tests\Functional;

use Carbon\Carbon;
use Ensi\LaravelAuditing\Events\Auditing;
use Ensi\LaravelAuditing\Exceptions\AuditingException;
use Ensi\LaravelAuditing\Facades\Transaction;
use Ensi\LaravelAuditing\Models\Audit;
use Ensi\LaravelAuditing\Tests\Data\Drivers\FakeDriver;
use Ensi\LaravelAuditing\Tests\Data\Models\Article;
use Ensi\LaravelAuditing\Tests\Data\Models\Factories\ApiModelFactory;
use Ensi\LaravelAuditing\Tests\Data\Models\Factories\ArticleFactory;
use Ensi\LaravelAuditing\Tests\Data\Models\User;
use Ensi\LaravelAuditing\Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Assert;
use InvalidArgumentException;

use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

uses(TestCase::class);

test('it will not audit models when running from the console', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.console', false);

    User::factory()->create();

    assertSame(1, User::query()->count());
    assertSame(0, Audit::query()->count());
});

test('it will audit models when running from the console', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.console', true);

    User::factory()->create();

    assertSame(1, User::query()->count());
    assertSame(1, Audit::query()->count());
});


test('it will always audit models when not running from the console', function () {
    /** @var TestCase $this */

    App::shouldReceive('runningInConsole')
        ->andReturn(false);

    $this->app['config']->set('laravel-auditing.console', false);

    User::factory()->create();

    assertSame(1, User::query()->count());
    assertSame(1, Audit::query()->count());
});


test('it will not audit the retrieving event', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.console', true);

    User::factory()->create();

    assertSame(1, User::query()->count());
    assertSame(1, Audit::query()->count());

    User::first();

    assertSame(1, Audit::query()->count());
    assertSame(1, User::query()->count());
});


test('it will audit the retrieving event', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.console', true);
    $this->app['config']->set('laravel-auditing.events', [
        'created',
        'retrieved',
    ]);

    User::factory()->create();

    assertSame(1, User::query()->count());
    assertSame(1, Audit::query()->count());

    User::first();

    assertSame(1, User::query()->count());
    assertSame(2, Audit::query()->count());
});


test('it will audit the retrieved event', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.events', [
        'retrieved',
    ]);

    Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ]);

    Article::first();

    $audit = Audit::first();

    assertEmpty($audit->old_values);

    assertEmpty($audit->new_values);
});


test('it will audit the created event', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.events', [
        'created',
    ]);

    Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ]);

    $audit = Audit::first();

    assertEmpty($audit->old_values);

    Assert::assertArraySubset([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ], $audit->new_values, true);
});


test('it will audit the updated event', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.events', [
        'updated',
    ]);

    $article = Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ]);

    $now = Carbon::now();

    $article->update([
        'content' => 'First step: install the laravel-auditing package.',
        'published_at' => $now,
        'reviewed' => 1,
    ]);

    $audit = Audit::first();

    Assert::assertArraySubset([
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ], $audit->old_values, true);

    Assert::assertArraySubset([
        'content' => 'First step: install the laravel-auditing package.',
        'published_at' => $now->toDateTimeString(),
        'reviewed' => 1,
    ], $audit->new_values, true);
});


test('it will audit the deleted event', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.events', [
        'deleted',
    ]);

    $article = Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ]);

    $article->delete();

    $audit = Audit::first();

    Assert::assertArraySubset([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ], $audit->old_values, true);

    assertEmpty($audit->new_values);
});


test('it will audit the restored event', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.events', [
        'restored',
    ]);

    $article = Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ]);

    $article->delete();
    $article->restore();

    $audit = Audit::first();

    assertEmpty($audit->old_values);

    Assert::assertArraySubset([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ], $audit->new_values, true);
});


test('it will keep all audits', function () {
    /** @var TestCase $this */

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

    assertSame(100, $article->audits()->count());
});


test('it will remove older audits above the threshold', function () {
    /** @var TestCase $this */

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

    assertSame(10, $article->audits()->count());
});


test('it will not audit due to unsupported driver', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.driver', 'foo');

    Article::factory()->create();
})->expectExceptionObject(new InvalidArgumentException('Driver [foo] not supported.'));


test('it will not audit due to class without driver interface', function () {
    /** @var TestCase $this */

    // We just pass a FQCN that does not implement the AuditDriver interface
    $this->app['config']->set('laravel-auditing.driver', FakeDriver::class);

    Article::factory()->create();
})->expectExceptionObject(new AuditingException('The driver must implement the AuditDriver contract'));


test('it will audit using the default driver', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.driver', null);

    Article::factory()->create([
        'title' => 'How To Audit Using The Fallback Driver',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ]);

    $audit = Audit::first();

    assertEmpty($audit->old_values);

    Assert::assertArraySubset([
        'title' => 'How To Audit Using The Fallback Driver',
        'content' => 'N/A',
        'published_at' => null,
        'reviewed' => 0,
    ], $audit->new_values, true);
});


test('it will cancel the audit from an event listener', function () {
    /** @var TestCase $this */

    Event::listen(Auditing::class, function () {
        return false;
    });

    Article::factory()->create();

    assertNull(Audit::first());
});


test('it disables and enables auditing back again', function () {
    /** @var TestCase $this */

    // Auditing is enabled by default
    assertFalse(Article::$auditingDisabled);

    Article::factory()->create();

    assertSame(1, Article::count());
    assertSame(1, Audit::count());

    // Disable Auditing
    Article::disableAuditing();
    assertTrue(Article::$auditingDisabled);

    Article::factory()->create();

    assertSame(2, Article::count());
    assertSame(1, Audit::count());

    // Re-enable Auditing
    Article::enableAuditing();
    assertFalse(Article::$auditingDisabled);

    Article::factory()->create();

    assertSame(2, Audit::count());
    assertSame(3, Article::count());
});


test('it adds transaction attributes to audit', function () {
    /** @var TestCase $this */

    DB::transaction(function () {
        /** @var Audit $audit */
        $audit = ArticleFactory::new()->create()->audits()->first();

        assertEquals($audit->transaction_uid, Transaction::uid()->toString());
        assertEquals($audit->transaction_time, Transaction::timestamp());
    });
});


test('it adds root entity to audit', function () {
    /** @var TestCase $this */

    /** @var Article $article */
    $article = ArticleFactory::new()->create();

    DB::transaction(function () use ($article) {
        Transaction::setRootEntity($article);

        /** @var Audit $audit */
        $audit = ApiModelFactory::new()->create()->audits()->first();

        assertEquals($article->getKey(), $audit->root_entity_id);
        assertEquals($article->getMorphClass(), $audit->root_entity_type);
    });
});

test('it will audit models when values are empty', function () {
    /** @var TestCase $this */

    $model = Article::factory()->create([
        'reviewed' => 0,
    ]);

    $model->reviewed = 1;
    $model->save();

    assertSame(1, Article::count());
    assertSame(2, Audit::count());
});

test('it will not audit models when values are empty', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.empty_values', false);

    $article = Article::factory()->create();

    $article->auditExclude = [
        'title',
    ];

    $article->title = '1';
    $article->save();

    assertSame(1, Article::count());
    assertSame(1, Audit::count());
});

test('it will audit retrieved event even if audit empty is disabled', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.empty_values', false);
    $this->app['config']->set('laravel-auditing.allowed_empty_values', ['retrieved']);
    $this->app['config']->set('laravel-auditing.events', [
        'created',
        'retrieved',
    ]);

    /** @var Article $model */
    Article::factory()->create();

    Article::find(1);

    assertSame(2, Audit::count());
});

test('it will not audit retrieved event if audit empty is disabled and retrieved event of empty values not allowed', function () {
    /** @var TestCase $this */

    $this->app['config']->set('laravel-auditing.empty_values', false);
    $this->app['config']->set('laravel-auditing.allowed_empty_values', []);
    $this->app['config']->set('laravel-auditing.events', [
        'created',
        'retrieved',
    ]);

    /** @var Article $model */
    Article::factory()->create();

    Article::find(1);

    assertSame(1, Audit::count());
});
