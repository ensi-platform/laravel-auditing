<?php

namespace Ensi\LaravelAuditing\Tests\Unit;

use Carbon\Carbon;
use DateTimeInterface;
use Ensi\LaravelAuditing\Encoders\Base64Encoder;
use Ensi\LaravelAuditing\Facades\Subject;
use Ensi\LaravelAuditing\Facades\Transaction;
use Ensi\LaravelAuditing\Models\Audit;
use Ensi\LaravelAuditing\Redactors\LeftRedactor;
use Ensi\LaravelAuditing\Tests\Data\Models\Article;
use Ensi\LaravelAuditing\Tests\Data\Models\Factories\AuditFactory;
use Ensi\LaravelAuditing\Tests\Data\Models\User;
use Ensi\LaravelAuditing\Tests\Data\Models\VirtualUser;
use Ensi\LaravelAuditing\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Assert;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

uses(TestCase::class);

test('it resolves audit data', function () {
    $now = Carbon::now();

    /** @var Article $article */
    $article = Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => $now,
    ]);

    $audit = $article->audits()->first();

    assertCount(Article::RESOLVE_FIELDS_COUNT, $resolvedData = $audit->resolveData());

    Assert::assertArraySubset([
        'audit_id' => 1,
        'audit_event' => 'created',
        'audit_url' => 'console',
        'audit_ip_address' => '127.0.0.1',
        'audit_user_agent' => 'Symfony',
        'audit_tags' => null,
        'audit_created_at' => $audit->created_at->toJSON(),
        'audit_updated_at' => $audit->updated_at->toJSON(),
        'new_title' => 'How To Audit Eloquent Models',
        'new_content' => 'First step: install the laravel-auditing package.',
        'new_published_at' => $now->toDateTimeString(),
        'new_reviewed' => 1,
        'extra' => ['year' => $now->year],
    ], $resolvedData, true);
});

test('it resolves audit data including subject attributes', function () {
    $now = Carbon::now();

    $user = User::factory()->create([
        'is_admin' => 1,
        'first_name' => 'rick',
        'last_name' => 'Sanchez',
        'email' => 'rick@wubba-lubba-dub.dub',
    ]);

    Subject::attach($user);

    /** @var Article $article */
    $article = Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => $now,
    ]);

    $audit = $article->audits()->first();

    assertCount(Article::RESOLVE_FIELDS_COUNT + 1, $resolvedData = $audit->resolveData());

    Assert::assertArraySubset([
        'audit_id' => 2,
        'audit_event' => 'created',
        'audit_url' => 'console',
        'audit_ip_address' => '127.0.0.1',
        'audit_user_agent' => 'Symfony',
        'audit_tags' => null,
        'audit_created_at' => $audit->created_at->toJSON(),
        'audit_updated_at' => $audit->updated_at->toJSON(),
        'new_title' => 'How To Audit Eloquent Models',
        'new_content' => 'First step: install the laravel-auditing package.',
        'new_published_at' => $now->toDateTimeString(),
        'new_reviewed' => 1,
        'subject_id' => $user->getKey(),
    ], $resolvedData, true);
});

test('it resolves audit data including root entity', function () {
    $user = User::factory()->create();

    /** @var Article $article */
    $article = Article::factory()->create(['title' => 'title']);

    DB::transaction(function () use ($article, $user) {
        Transaction::setRootEntity($user);
        $article->update(['title' => 'new title']);
    });

    $audit = $article->audits()->latest()->first();

    Assert::assertArraySubset([
        'audit_id' => 3,
        'audit_event' => 'updated',
        'audit_url' => 'console',
        'audit_ip_address' => '127.0.0.1',
        'audit_user_agent' => 'Symfony',
        'audit_tags' => null,
        'audit_created_at' => $audit->created_at->toJSON(),
        'audit_updated_at' => $audit->updated_at->toJSON(),
        'old_title' => 'title',
        'new_title' => 'new title',
        "root_entity_id" => $user->id,
        "root_entity_type" => User::class,
    ], $audit->resolveData(), true);
});

test('it resolves audit data including user id', function () {
    $this->actingAs(new VirtualUser(), 'api');

    /** @var Article $article */
    $article = Article::factory()->create();

    $audit = $article->audits()->first();

    Assert::assertArraySubset([
        'user_id' => VirtualUser::ID,
    ], $audit->resolveData(), true);
});

test('it resolves audit data including default extra', function () {
    /** @var Article $article */
    $article = Article::factory()->create();

    $audit = $article->audits()->first();

    Assert::assertArraySubset([
        'extra' => null,
    ], $audit->resolveData(), true);
});

test('it returns the appropriate auditable data values', function () {
    $user = User::factory()->create([
        'is_admin' => 1,
        'first_name' => 'rick',
        'last_name' => 'Sanchez',
        'email' => 'rick@wubba-lubba-dub.dub',
    ]);

    /** @var Article $article */
    $audit = Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => Carbon::now(),
    ])->audits()->first();

    // Resolve data, making it available to the getDataValue() method
    assertCount(Article::RESOLVE_FIELDS_COUNT, $audit->resolveData());

    // Mutate value
    assertSame('HOW TO AUDIT ELOQUENT MODELS', $audit->getDataValue('new_title'));

    // Cast value
    assertTrue($audit->getDataValue('new_reviewed'));

    // Date value
    assertInstanceOf(DateTimeInterface::class, $audit->getDataValue('new_published_at'));

    // Original value
    assertSame('First step: install the laravel-auditing package.', $audit->getDataValue('new_content'));

    // Invalid value
    assertNull($audit->getDataValue('invalid_key'));
});

test('it returns audit metadata as array', function () {
    /** @var Article $article */
    $audit = Article::factory()->create()->audits()->first();

    assertCount(Article::AUDIT_META_FIELDS_COUNT, $metadata = $audit->getMetadata());

    Assert::assertArraySubset([
        'audit_id' => 1,
        'audit_event' => 'created',
        'audit_url' => 'console',
        'audit_ip_address' => '127.0.0.1',
        'audit_user_agent' => 'Symfony',
        'audit_tags' => null,
        'audit_created_at' => $audit->created_at->toJSON(),
        'audit_updated_at' => $audit->updated_at->toJSON(),
    ], $metadata, true);
});

test('it returns audit metadata including subject attributes as array', function () {
    $user = User::factory()->create([
        'is_admin' => 1,
        'first_name' => 'rick',
        'last_name' => 'Sanchez',
        'email' => 'rick@wubba-lubba-dub.dub',
    ]);

    Subject::attach($user);

    /** @var Article $article */
    $audit = Article::factory()->create()->audits()->first();

    assertCount(Article::AUDIT_META_FIELDS_COUNT + 1, $metadata = $audit->getMetadata());

    Assert::assertArraySubset([
        'audit_id' => 2,
        'audit_event' => 'created',
        'audit_url' => 'console',
        'audit_ip_address' => '127.0.0.1',
        'audit_user_agent' => 'Symfony',
        'audit_tags' => null,
        'audit_created_at' => $audit->created_at->toJSON(),
        'audit_updated_at' => $audit->updated_at->toJSON(),
        'subject_id' => $user->getKey(),
    ], $metadata, true);
});

test('it returns audit metadata as json string', function () {
    $this->travel(-1)->minutes();
    $now = now()->toJSON();

    /** @var Article $article */
    $audit = Article::factory()->create()->audits()->first();

    $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

    $expected = <<< EOF
{
    "audit_id": 1,
    "audit_event": "created",
    "audit_url": "console",
    "audit_ip_address": "127.0.0.1",
    "audit_user_agent": "Symfony",
    "audit_tags": null,
    "audit_created_at": "$now",
    "audit_updated_at": "$now",
    "root_entity_id": null,
    "root_entity_type": null,
    "subject_id": null,
    "subject_type": null,
    "transaction_uid": null,
    "transaction_time": "$now",
    "user_id": null,
    "extra": null
}
EOF;

    assertSame($expected, $metadata);
});

test('it returns audit metadata including subject attributes as json string', function () {
    $user = User::factory()->create([
        'is_admin' => 1,
        'first_name' => 'rick',
        'last_name' => 'Sanchez',
        'email' => 'rick@wubba-lubba-dub.dub',
    ]);

    Subject::attach($user);

    $this->travel(-1)->minutes();
    $now = now()->toJSON();
    $userId = $user->getKey();

    /** @var Article $article */
    $audit = Article::factory()->create()->audits()->first();

    $metadata = $audit->getMetadata(true, JSON_PRETTY_PRINT);

    $expected = <<< EOF
{
    "audit_id": 2,
    "audit_event": "created",
    "audit_url": "console",
    "audit_ip_address": "127.0.0.1",
    "audit_user_agent": "Symfony",
    "audit_tags": null,
    "audit_created_at": "$now",
    "audit_updated_at": "$now",
    "root_entity_id": null,
    "root_entity_type": null,
    "subject_id": $userId,
    "subject_type": "Ensi\\\\LaravelAuditing\\\\Tests\\\\Data\\\\Models\\\\User",
    "transaction_uid": null,
    "transaction_time": "$now",
    "user_id": $userId,
    "extra": null,
    "subject_name": "Rick"
}
EOF;

    assertSame($expected, $metadata);
});

test('it returns auditable modified attributes as array', function () {
    $now = Carbon::now()->milliseconds(0);

    /** @var Article $article */
    $audit = Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => $now,
    ])->audits()->first();

    assertCount(4, $modified = $audit->getModified());

    Assert::assertArraySubset([
        'title' => [
            'new' => 'HOW TO AUDIT ELOQUENT MODELS',
        ],
        'content' => [
            'new' => 'First step: install the laravel-auditing package.',
        ],
        'published_at' => [
            'new' => $now->toJSON(),
        ],
        'reviewed' => [
            'new' => true,
        ],
    ], $modified, true);
});

test('it returns auditable modified attributes as json string', function () {
    $now = Carbon::now()->milliseconds(0);
    $publishedAt = $now->toJSON();

    /** @var Article $article */
    $audit = Article::factory()->create([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => $now,
    ])->audits()->first();

    $modified = $audit->getModified(true, JSON_PRETTY_PRINT);

    $expected = <<< EOF
{
    "title": {
        "new": "HOW TO AUDIT ELOQUENT MODELS"
    },
    "content": {
        "new": "First step: install the laravel-auditing package."
    },
    "published_at": {
        "new": "$publishedAt"
    },
    "reviewed": {
        "new": true
    }
}
EOF;

    assertSame($expected, $modified);
});
test('it returns decoded auditable attributes', function () {
    $article = new class () extends Article {
        protected $table = 'articles';

        protected $attributeModifiers = [
            'title' => Base64Encoder::class,
            'content' => LeftRedactor::class,
        ];
    };

    // Audit with redacted/encoded attributes
    /** @var Audit $audit */
    $audit = AuditFactory::new()->create([
        'auditable_type' => get_class($article),
        'old_values' => [
            'title' => 'SG93IFRvIEF1ZGl0IE1vZGVscw==',
            'content' => '##A',
            'reviewed' => 0,
        ],
        'new_values' => [
            'title' => 'SG93IFRvIEF1ZGl0IEVsb3F1ZW50IE1vZGVscw==',
            'content' => '############################################kage.',
            'reviewed' => 1,
        ],
    ]);

    assertCount(3, $modified = $audit->getModified());

    Assert::assertArraySubset([
        'title' => [
            'new' => 'HOW TO AUDIT ELOQUENT MODELS',
            'old' => 'HOW TO AUDIT MODELS',
        ],
        'content' => [
            'new' => '############################################kage.',
            'old' => '##A',
        ],
        'reviewed' => [
            'new' => true,
            'old' => false,
        ],
    ], $modified, true);
});

test('it returns tags', function () {
    /** @var Audit $audit */
    $audit = AuditFactory::new()->create([
        'tags' => 'foo,bar,baz',
    ]);

    assertIsArray($audit->getTags());
    Assert::assertArraySubset([
        'foo',
        'bar',
        'baz',
    ], $audit->getTags(), true);
});

test('it returns empty tags', function () {
    /** @var Audit $audit */
    $audit = AuditFactory::new()->create([
        'tags' => null,
    ]);

    assertIsArray($audit->getTags());
    assertEmpty($audit->getTags());
});
