<?php

namespace Ensi\LaravelAuditing\Tests\Unit;

use Carbon\Carbon;
use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\Encoders\Base64Encoder;
use Ensi\LaravelAuditing\Exceptions\AuditableTransitionException;
use Ensi\LaravelAuditing\Exceptions\AuditingException;
use Ensi\LaravelAuditing\Facades\Subject;
use Ensi\LaravelAuditing\Redactors\LeftRedactor;
use Ensi\LaravelAuditing\Redactors\RightRedactor;
use Ensi\LaravelAuditing\Tests\Data\Models\ApiModel;
use Ensi\LaravelAuditing\Tests\Data\Models\Article;
use Ensi\LaravelAuditing\Tests\Data\Models\Factories\AuditFactory;
use Ensi\LaravelAuditing\Tests\Data\Models\User;
use Ensi\LaravelAuditing\Tests\Data\Models\VirtualUser;
use Ensi\LaravelAuditing\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Testing\Assert;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

use ReflectionClass;

uses(TestCase::class);

beforeEach(function () {
    // Clear morph maps
    Relation::morphMap([], false);
});

test('it will not audit models when running from the console', function () {
    config()->set('laravel-auditing.console', false);

    assertFalse(Article::isAuditingEnabled());
});

test('it will audit models when running from the console', function () {
    config()->set('laravel-auditing.console', true);

    assertTrue(Article::isAuditingEnabled());
});

test('it will always audit models when not running from the console', function () {
    App::shouldReceive('runningInConsole')
        ->andReturn(false);

    config()->set('laravel-auditing.console', false);

    assertTrue(Article::isAuditingEnabled());
});

test('it will not boot trait when static flag is set', function () {
    App::spy();

    Article::$auditingDisabled = true;

    new Article();

    App::shouldNotHaveReceived('runningInConsole');

    Article::$auditingDisabled = false;
});

test('it returns null when the audit event is not set', function () {
    $model = new Article();

    assertNull($model->getAuditEvent());
});

test('it returns the audit event that has been set', function () {
    $model = new Article();
    $model->setAuditEvent('created');

    assertSame('created', $model->getAuditEvent());
});

test('it returns the default audit events', function () {
    $model = new Article();

    Assert::assertArraySubset([
        'created',
        'updated',
        'deleted',
        'restored',
    ], $model->getAuditEvents(), true);
});

test('it returns the custom audit events from attribute', function () {
    $model = new Article();

    $model->auditEvents = [
        'published' => 'getPublishedEventAttributes',
        'archived',
    ];

    Assert::assertArraySubset([
        'published' => 'getPublishedEventAttributes',
        'archived',
    ], $model->getAuditEvents(), true);
});

test('it returns the custom audit events from config', function () {
    config()->set('laravel-auditing.events', [
        'published' => 'getPublishedEventAttributes',
        'archived',
    ]);

    $model = new Article();

    Assert::assertArraySubset([
        'published' => 'getPublishedEventAttributes',
        'archived',
    ], $model->getAuditEvents(), true);
});

test('it is not ready for auditing with custom event', function () {
    $model = new Article();

    $model->setAuditEvent('published');
    assertFalse($model->readyForAuditing());
});

test('it is ready for auditing with custom events', function () {
    $model = new Article();

    $model->auditEvents = [
        'published' => 'getPublishedEventAttributes',
        '*ted' => 'getMultiEventAttributes',
        'archived',
    ];

    $model->setAuditEvent('published');
    assertTrue($model->readyForAuditing());

    $model->setAuditEvent('archived');
    assertTrue($model->readyForAuditing());

    $model->setAuditEvent('redacted');
    assertTrue($model->readyForAuditing());
});

test('it is ready for auditing with regular events', function () {
    $model = new Article();

    $model->setAuditEvent('created');
    assertTrue($model->readyForAuditing());

    $model->setAuditEvent('updated');
    assertTrue($model->readyForAuditing());

    $model->setAuditEvent('deleted');
    assertTrue($model->readyForAuditing());

    $model->setAuditEvent('restored');
    assertTrue($model->readyForAuditing());
});

test('it fails when an invalid audit event is set', function () {
    $model = new Article();

    $model->setAuditEvent('published');

    $model->toAudit();
})->expectExceptionObject(new AuditingException('A valid audit event has not been set'));

test('it fails when the custom attribute getters are missing', function (
    string $event,
    array $auditEvents,
    string $exceptionMessage
) {
    $this->expectException(AuditingException::class);
    $this->expectExceptionMessage($exceptionMessage);

    $model = new Article();

    $model->auditEvents = $auditEvents;

    $model->setAuditEvent($event);

    $model->toAudit();
})->with([
    [
        'published',
        [
            'published' => 'getPublishedEventAttributes',
        ],
        'Unable to handle "published" event, getPublishedEventAttributes() method missing',
    ],
    [
        'archived',
        [
            'archived',
        ],
        'Unable to handle "archived" event, getArchivedEventAttributes() method missing',
    ],
    [
        'redacted',
        [
            '*ed',
        ],
        'Unable to handle "redacted" event, getRedactedEventAttributes() method missing',
    ],
    [
        'redacted',
        [
            '*ed' => 'getMultiEventAttributes',
        ],
        'Unable to handle "redacted" event, getMultiEventAttributes() method missing',
    ],
]);

test('it fails when the ip address resolver implementation is invalid', function () {
    config()->set('laravel-auditing.resolver.ip_address', null);

    $model = new Article();

    $model->setAuditEvent('created');

    $model->toAudit();
})->expectExceptionObject(new AuditingException('Invalid IpAddressResolver implementation'));

test('it fails when the url resolver implementation is invalid', function () {
    config()->set('laravel-auditing.resolver.url', null);

    $model = new Article();

    $model->setAuditEvent('created');

    $model->toAudit();
})->expectExceptionObject(new AuditingException('Invalid UrlResolver implementation'));

test('it fails when the user agent resolver implementation is invalid', function () {
    config()->set('laravel-auditing.resolver.user_agent', null);

    $model = new Article();

    $model->setAuditEvent('created');

    $model->toAudit();
})->expectExceptionObject(new AuditingException('Invalid UserAgentResolver implementation'));

test('it fails when the user resolver implementation is invalid', function () {
    config()->set('laravel-auditing.resolver.user', null);

    $model = new Article();

    $model->setAuditEvent('created');

    $model->toAudit();
})->expectExceptionObject(new AuditingException('Invalid UserResolver implementation'));

test('it returns the audit data', function () {
    $now = Carbon::now();

    /** @var Article $model */
    $model = Article::factory()->make([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => $now,
    ]);

    $model->setAuditEvent('created');

    assertCount(Article::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

    Assert::assertArraySubset([
        'old_values' => [],
        'new_values' => [
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now->toDateTimeString(),
        ],
        'event' => 'created',
        'auditable_id' => null,
        'auditable_type' => Article::class,
        'subject_id' => null,
        'subject_type' => null,
        'url' => 'console',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Symfony',
        'tags' => null,
    ], $auditData, true);
});

test('it returns the audit data including subject attributes', function () {
    $user = User::factory()->create();
    Subject::attach($user);
    $now = Carbon::now();

    /** @var Article $model */
    $model = Article::factory()->make([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => $now,
    ]);

    $model->setAuditEvent('created');

    assertCount(Article::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

    Assert::assertArraySubset([
        'old_values' => [],
        'new_values' => [
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now->toDateTimeString(),
        ],
        'event' => 'created',
        'auditable_id' => null,
        'auditable_type' => Article::class,
        'subject_id' => $user->getKey(),
        'subject_type' => User::class,
        'url' => 'console',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Symfony',
        'user_id' => null,
        'tags' => null,
    ], $auditData, true);
});

test('it returns the audit data including user id', function (
    string $guard,
    string $driver,
    ?string $id
) {
    config()->set('laravel-auditing.user.guards', [$guard]);

    $user = User::factory()->create();
    $this->actingAs($user, $driver);
    $now = Carbon::now();

    /** @var Article $model */
    $model = Article::factory()->make([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => $now,
    ]);

    $model->setAuditEvent('created');

    assertCount(Article::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

    Assert::assertArraySubset([
        'old_values' => [],
        'new_values' => [
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now->toDateTimeString(),
        ],
        'event' => 'created',
        'auditable_id' => null,
        'auditable_type' => Article::class,
        'subject_id' => null,
        'subject_type' => null,
        'url' => 'console',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Symfony',
        'user_id' => $id,
        'tags' => null,
    ], $auditData, true);
})->with([
    ['api', 'web', null],
    ['web', 'api', null],
    ['api', 'api', '1'],
    ['web', 'web', '1'],
]);

test('it returns the audit data including virtual user id', function () {
    $this->actingAs(new VirtualUser(), 'api');

    $now = Carbon::now();

    /** @var Article $model */
    $model = Article::factory()->make([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => $now,
    ]);

    $model->setAuditEvent('created');

    assertCount(Article::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

    Assert::assertArraySubset([
        'old_values' => [],
        'new_values' => [
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => $now->toDateTimeString(),
        ],
        'event' => 'created',
        'auditable_id' => null,
        'auditable_type' => Article::class,
        'subject_id' => null,
        'subject_type' => null,
        'url' => 'console',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Symfony',
        'user_id' => VirtualUser::ID,
        'tags' => null,
    ], $auditData, true);
});

test('it returns the audit data including extra', function () {
    $now = Carbon::now();

    /** @var Article $model */
    $model = Article::factory()->make([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => $now,
    ]);

    $model->setAuditEvent('created');

    assertCount(Article::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

    Assert::assertArraySubset([
        'extra' => [
            'year' => $now->year,
        ],
        'event' => 'created',
        'auditable_id' => null,
        'auditable_type' => Article::class,
    ], $auditData, true);
});

test('it returns the default extra', function () {
    /** @var Article $model */
    $model = Article::factory()->make();
    $model->setAuditEvent('created');

    Assert::assertArraySubset([
        'extra' => null,
    ], $model->toAudit(), true);
});

test('it excludes attributes from the audit data when in strict mode', function () {
    config()->set('laravel-auditing.strict', true);

    $model = Article::factory()->make([
        'title' => 'How To Audit Eloquent Models',
        'content' => 'First step: install the laravel-auditing package.',
        'reviewed' => 1,
        'published_at' => Carbon::now(),
    ]);

    $model->setHidden([
        'reviewed',
    ]);

    $model->setVisible([
        'title',
        'content',
    ]);

    $model->setAuditEvent('created');

    assertCount(Article::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

    Assert::assertArraySubset([
        'old_values' => [],
        'new_values' => [
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
        ],
        'event' => 'created',
        'auditable_id' => null,
        'auditable_type' => Article::class,
        'subject_id' => null,
        'subject_type' => null,
        'url' => 'console',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Symfony',
        'tags' => null,
    ], $auditData, true);
});

test('it fails when the attribute modifier implementation is invalid', function () {
    /** @var Article $model */
    $model = Article::factory()->make();

    $model->attributeModifiers = [
        'title' => 'invalidAttributeRedactorOrEncoder',
    ];

    $model->setAuditEvent('created');

    $model->toAudit();
})->expectExceptionObject(new AuditingException('Invalid AttributeModifier implementation: invalidAttributeRedactorOrEncoder'));

test('it modifies the audit attributes successfully', function () {
    /** @var Article $model */
    $model = Article::factory()->make([
        'title' => 'How To Audit Models',
        'content' => 'N/A',
        'reviewed' => 0,
        'published_at' => null,
    ]);

    $now = Carbon::now();

    $model->syncOriginal();

    $model->title = 'How To Audit Eloquent Models';
    $model->content = 'First step: install the laravel-auditing package.';
    $model->reviewed = 1;
    $model->published_at = $now;

    $model->setAuditEvent('updated');

    $model->attributeModifiers = [
        'title' => RightRedactor::class,
        'content' => LeftRedactor::class,
        'reviewed' => Base64Encoder::class,
    ];

    Assert::assertArraySubset([
        'old_values' => [
            'title' => 'Ho#################',
            'content' => '##A',
            'published_at' => null,
            'reviewed' => 'MA==',
        ],
        'new_values' => [
            'title' => 'How#########################',
            'content' => '############################################kage.',
            'published_at' => $now->toDateTimeString(),
            'reviewed' => 'MQ==',
        ],
    ], $model->toAudit(), true);
});

test('it transforms the audit data', function () {
    $model = new class () extends Article {
        protected $attributes = [
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => '2012-06-14 15:03:00',
        ];

        public function transformAudit(array $data): array
        {
            $data['new_values']['slug'] = Str::slug($data['new_values']['title']);

            return $data;
        }
    };

    $model->setAuditEvent('created');

    assertCount(Article::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

    Assert::assertArraySubset([
        'new_values' => [
            'title' => 'How To Audit Eloquent Models',
            'content' => 'First step: install the laravel-auditing package.',
            'reviewed' => 1,
            'published_at' => '2012-06-14 15:03:00',
            'slug' => 'how-to-audit-eloquent-models',
        ],
    ], $auditData, true);
});

test('it returns the default attributes to be included in the audit', function () {
    $model = new Article();

    Assert::assertArraySubset([], $model->getAuditInclude(), true);
});

test('it returns the custom attributes to be included in the audit', function () {
    $model = new Article();

    $model->auditInclude = [
        'title',
        'content',
    ];

    Assert::assertArraySubset([
        'title',
        'content',
    ], $model->getAuditInclude(), true);
});

test('it returns the default attributes to be excluded from the audit', function () {
    $model = new Article();

    Assert::assertArraySubset([], $model->getAuditExclude(), true);
});

test('it returns the custom attributes to be excluded from the audit', function () {
    $model = new Article();

    $model->auditExclude = [
        'published_at',
    ];

    Assert::assertArraySubset([
        'published_at',
    ], $model->getAuditExclude(), true);
});

test('it returns the default audit strict value', function () {
    $model = new Article();

    assertFalse($model->getAuditStrict());
});

test('it returns the custom audit strict value from attribute', function () {
    $model = new Article();

    $model->auditStrict = true;

    assertTrue($model->getAuditStrict());
});

test('it returns the custom audit strict value from config', function () {
    config()->set('laravel-auditing.strict', true);

    $model = new Article();

    assertTrue($model->getAuditStrict());
});

test('it returns the default audit timestamps value', function () {
    $model = new Article();

    assertFalse($model->getAuditTimestamps());
});

test('it returns the custom audit timestamps value from attribute', function () {
    $model = new Article();

    $model->auditTimestamps = true;

    assertTrue($model->getAuditTimestamps());
});

test('it returns the custom audit timestamps value from config', function () {
    config()->set('laravel-auditing.timestamps', true);

    $model = new Article();

    assertTrue($model->getAuditTimestamps());
});

test('it returns the default audit driver value', function () {
    $model = new Article();

    assertSame('database', $model->getAuditDriver());
});

test('it returns the custom audit driver value from attribute', function () {
    $model = new Article();

    $model->auditDriver = 'RedisDriver';

    assertSame('RedisDriver', $model->getAuditDriver());
});

test('it returns the custom audit driver value from config', function () {
    config()->set('laravel-auditing.driver', 'RedisDriver');

    $model = new Article();

    assertSame('RedisDriver', $model->getAuditDriver());
});

test('it returns the default audit threshold value', function () {
    $model = new Article();

    assertSame(0, $model->getAuditThreshold());
});

test('it returns the custom audit threshold value from attribute', function () {
    $model = new Article();

    $model->auditThreshold = 10;

    assertSame(10, $model->getAuditThreshold());
});

test('it returns the custom audit threshold value from config', function () {
    config()->set('laravel-auditing.threshold', 200);

    $model = new Article();

    assertSame(200, $model->getAuditThreshold());
});

test('it returns the default generated audit tags', function () {
    $model = new Article();

    Assert::assertArraySubset([], $model->generateTags(), true);
});

test('it returns the custom generated audit tags', function () {
    $model = new class () extends Article {
        public function generateTags(): array
        {
            return [
                'foo',
                'bar',
            ];
        }
    };

    Assert::assertArraySubset([
        'foo',
        'bar',
    ], $model->generateTags(), true);
});

test('it fails to transition when the audit auditable type does not match the model type', function () {

    $audit = AuditFactory::new()->make([
        'auditable_type' => User::class,
    ]);

    $model = new Article();

    $model->transitionTo($audit);
})->expectExceptionObject(new AuditableTransitionException('Expected Auditable type Ensi\LaravelAuditing\Tests\Data\Models\Article, got Ensi\LaravelAuditing\Tests\Data\Models\User instead'));

test('it fails to transition when the audit auditable type does not match the morph map value', function () {
    Relation::morphMap([
        'articles' => Article::class,
    ]);

    $audit = AuditFactory::new()->make([
        'auditable_type' => 'users',
    ]);

    $model = new Article();

    $model->transitionTo($audit);
})->expectExceptionObject(new AuditableTransitionException('Expected Auditable type articles, got users instead'));

test('it fails to transition when the audit auditable id does not match the model id', function () {
    $firstAudit = Article::factory()->create()->audits()->first();
    $secondModel = Article::factory()->create();

    $secondModel->transitionTo($firstAudit);
})->expectExceptionObject(new AuditableTransitionException('Expected Auditable id 2, got 1 instead'));

test('it fails to transition when the audit auditable id type does not match the model id type', function () {
    $model = Article::factory()->create();

    $audit = AuditFactory::new()->create([
        'auditable_type' => Article::class,
        'auditable_id' => (string)$model->id,
    ]);

    // Make sure the auditable_id isn't being cast
    $auditReflection = new ReflectionClass($audit);

    $auditCastsProperty = $auditReflection->getProperty('casts');
    $auditCastsProperty->setAccessible(true);
    $auditCastsProperty->setValue($audit, [
        'old_values' => 'json',
        'new_values' => 'json',
    ]);

    $model->transitionTo($audit);
})->expectExceptionObject(new AuditableTransitionException('Expected Auditable id 1, got 1 instead'));

test('it transitions when the audit auditable id type does not match the model id type', function () {
    /** @var Article $model */
    $model = Article::factory()->create();

    // Key depends on type
    if ($model->getKeyType() == 'string') {
        $key = (string)$model->id;
    } else {
        $key = (int)$model->id;
    }

    $audit = AuditFactory::new()->create([
        'auditable_type' => Article::class,
        'auditable_id' => $key,
    ]);

    assertInstanceOf(Auditable::class, $model->transitionTo($audit));
});

test('it fails to transition when an attribute redactor is set', function () {
    $model = Article::factory()->create();

    $model->attributeModifiers = [
        'title' => RightRedactor::class,
    ];

    $audit = AuditFactory::new()->create([
        'auditable_id' => $model->getKey(),
        'auditable_type' => Article::class,
    ]);

    $model->transitionTo($audit);
})->expectExceptionObject(new AuditableTransitionException('Cannot transition states when an AttributeRedactor is set'));

test('it fails to transition when the auditable attribute compatibility is not met', function () {
    $model = Article::factory()->create();

    $incompatibleAudit = AuditFactory::new()->create([
        'event' => 'created',
        'auditable_id' => $model->getKey(),
        'auditable_type' => Article::class,
        'old_values' => [],
        'new_values' => [
            'subject' => 'Culpa qui rerum excepturi quisquam quia officiis.',
            'text' => 'Magnam enim suscipit officiis tempore ut quis harum.',
        ],
    ]);

    try {
        $model->transitionTo($incompatibleAudit);
    } catch (AuditableTransitionException $e) {
        assertSame(
            'Incompatibility between [Ensi\LaravelAuditing\Tests\Data\Models\Article:1] and [Ensi\LaravelAuditing\Models\Audit:3]',
            $e->getMessage()
        );

        Assert::assertArraySubset([
            'subject',
            'text',
        ], $e->getIncompatibilities(), true);
    }
});

test('it transitions to another model state', function (
    bool $morphMap,
    array $oldValues,
    array $newValues,
    array $oldExpectation,
    array $newExpectation
) {
    $models = Article::factory()->count(2)->create([
        'title' => 'Facilis voluptas qui impedit deserunt vitae quidem.',
        'content' => 'Consectetur distinctio nihil eveniet cum. Expedita dolores animi dolorum eos repellat rerum.',
    ]);

    if ($morphMap) {
        Relation::morphMap([
            'articles' => Article::class,
        ]);
    }

    $auditableType = $morphMap ? 'articles' : Article::class;

    $audits = $models->map(function (Article $model) use ($auditableType, $oldValues, $newValues) {
        return AuditFactory::new()->create([
            'auditable_id' => $model->getKey(),
            'auditable_type' => $auditableType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    });

    // Transition with old values
    assertInstanceOf(Auditable::class, $models[0]->transitionTo($audits[0], true));
    assertSame($oldExpectation, $models[0]->getDirty());

    // Transition with new values
    assertInstanceOf(Auditable::class, $models[1]->transitionTo($audits[1]));
    assertSame($newExpectation, $models[1]->getDirty());
})->with([
    //
    // Audit data and expectations for retrieved event
    //
    [
        // Morph Map
        false,

        // Old values
        [],

        // New values
        [],

        // Expectation when transitioning with old values
        [],

        // Expectation when transitioning with new values
        [],
    ],

    //
    // Audit data and expectations for created/restored event
    //
    [
        // Morph Map
        true,

        // Old values
        [],

        // New values
        [
            'title' => 'Nullam egestas interdum eleifend.',
            'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
        ],

        // Expectation when transitioning with old values
        [],

        // Expectation when transitioning with new values
        [
            'title' => 'NULLAM EGESTAS INTERDUM ELEIFEND.',
            'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
        ],
    ],

    //
    // Audit data and expectations for updated event
    //
    [
        // Morph Map
        false,

        // Old values
        [
            'title' => 'Vivamus a urna et lorem faucibus malesuada nec nec magna.',
            'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
        ],

        // New values
        [
            'title' => 'Nullam egestas interdum eleifend.',
            'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
        ],

        // Expectation when transitioning with old values
        [
            'title' => 'VIVAMUS A URNA ET LOREM FAUCIBUS MALESUADA NEC NEC MAGNA.',
            'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
        ],

        // Expectation when transitioning with new values
        [
            'title' => 'NULLAM EGESTAS INTERDUM ELEIFEND.',
            'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
        ],
    ],

    //
    // Audit data and expectations for deleted event
    //
    [
        // Morph Map
        true,

        // Old values
        [
            'title' => 'Vivamus a urna et lorem faucibus malesuada nec nec magna.',
            'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
        ],

        // New values
        [],

        // Expectation when transitioning with old values
        [
            'title' => 'VIVAMUS A URNA ET LOREM FAUCIBUS MALESUADA NEC NEC MAGNA.',
            'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
        ],

        // Expectation when transitioning with new values
        [],
    ],
]);

test('it works with string key models', function () {
    $model = ApiModel::factory()->create();
    $model->save();
    $model->refresh();

    assertCount(1, $model->audits);

    $model->content = 'Something else';
    $model->save();
    $model->refresh();

    assertCount(2, $model->audits);
});
