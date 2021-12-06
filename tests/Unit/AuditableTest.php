<?php

namespace Ensi\LaravelAuditing\Tests\Unit;

use Carbon\Carbon;
use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\Database\Factories\AuditFactory;
use Ensi\LaravelAuditing\Encoders\Base64Encoder;
use Ensi\LaravelAuditing\Exceptions\AuditableTransitionException;
use Ensi\LaravelAuditing\Exceptions\AuditingException;
use Ensi\LaravelAuditing\Facades\Subject;
use Ensi\LaravelAuditing\Models\Audit;
use Ensi\LaravelAuditing\Redactors\LeftRedactor;
use Ensi\LaravelAuditing\Redactors\RightRedactor;
use Ensi\LaravelAuditing\Tests\AuditingTestCase;
use Ensi\LaravelAuditing\Tests\Models\ApiModel;
use Ensi\LaravelAuditing\Tests\Models\Article;
use Ensi\LaravelAuditing\Tests\Models\User;
use Ensi\LaravelAuditing\Tests\Models\VirtualUser;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Testing\Assert;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use ReflectionClass;

class AuditableTest extends AuditingTestCase
{
    private const AUDIT_FIELDS_COUNT = 14;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        // Clear morph maps
        Relation::morphMap([], false);
    }

    /**
     * @group Auditable::isAuditingEnabled
     * @test
     */
    public function itWillNotAuditModelsWhenRunningFromTheConsole()
    {
        $this->app['config']->set('laravel-auditing.console', false);

        $this->assertFalse(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::isAuditingEnabled
     * @test
     */
    public function itWillAuditModelsWhenRunningFromTheConsole()
    {
        $this->app['config']->set('laravel-auditing.console', true);

        $this->assertTrue(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::isAuditingEnabled
     * @test
     */
    public function itWillAlwaysAuditModelsWhenNotRunningFromTheConsole()
    {
        App::shouldReceive('runningInConsole')
            ->andReturn(false);

        $this->app['config']->set('laravel-auditing.console', false);

        $this->assertTrue(Article::isAuditingEnabled());
    }

    /**
     * @group Auditable::bootAuditable
     * @test
     */
    public function itWillNotBootTraitWhenStaticFlagIsSet()
    {
        App::spy();

        Article::$auditingDisabled = true;

        new Article();

        App::shouldNotHaveReceived('runningInConsole');

        Article::$auditingDisabled = false;
    }

    /**
     * @group Auditable::getAuditEvent
     * @test
     */
    public function itReturnsNullWhenTheAuditEventIsNotSet()
    {
        $model = new Article();

        $this->assertNull($model->getAuditEvent());
    }

    /**
     * @group Auditable::getAuditEvent
     * @test
     */
    public function itReturnsTheAuditEventThatHasBeenSet()
    {
        $model = new Article();
        $model->setAuditEvent('created');

        $this->assertSame('created', $model->getAuditEvent());
    }

    /**
     * @group Auditable::getAuditEvents
     * @test
     */
    public function itReturnsTheDefaultAuditEvents()
    {
        $model = new Article();

        Assert::assertArraySubset([
            'created',
            'updated',
            'deleted',
            'restored',
        ], $model->getAuditEvents(), true);
    }

    /**
     * @group Auditable::getAuditEvents
     * @test
     */
    public function itReturnsTheCustomAuditEventsFromAttribute()
    {
        $model = new Article();

        $model->auditEvents = [
            'published' => 'getPublishedEventAttributes',
            'archived',
        ];

        Assert::assertArraySubset([
            'published' => 'getPublishedEventAttributes',
            'archived',
        ], $model->getAuditEvents(), true);
    }

    /**
     * @group Auditable::getAuditEvents
     * @test
     */
    public function itReturnsTheCustomAuditEventsFromConfig()
    {
        $this->app['config']->set('laravel-auditing.events', [
            'published' => 'getPublishedEventAttributes',
            'archived',
        ]);

        $model = new Article();

        Assert::assertArraySubset([
            'published' => 'getPublishedEventAttributes',
            'archived',
        ], $model->getAuditEvents(), true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     * @test
     */
    public function itIsNotReadyForAuditingWithCustomEvent()
    {
        $model = new Article();

        $model->setAuditEvent('published');
        $this->assertFalse($model->readyForAuditing());
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     * @test
     */
    public function itIsReadyForAuditingWithCustomEvents()
    {
        $model = new Article();

        $model->auditEvents = [
            'published' => 'getPublishedEventAttributes',
            '*ted'      => 'getMultiEventAttributes',
            'archived',
        ];

        $model->setAuditEvent('published');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('archived');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('redacted');
        $this->assertTrue($model->readyForAuditing());
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::readyForAuditing
     * @test
     */
    public function itIsReadyForAuditingWithRegularEvents()
    {
        $model = new Article();

        $model->setAuditEvent('created');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('updated');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('deleted');
        $this->assertTrue($model->readyForAuditing());

        $model->setAuditEvent('restored');
        $this->assertTrue($model->readyForAuditing());
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenAnInvalidAuditEventIsSet()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('A valid audit event has not been set');

        $model = new Article();

        $model->setAuditEvent('published');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     *
     * @dataProvider auditCustomAttributeGetterFailTestProvider
     *
     * @param string $event
     * @param array  $auditEvents
     * @param string $exceptionMessage
     */
    public function itFailsWhenTheCustomAttributeGettersAreMissing(
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
    }

    /**
     * @return array
     */
    public function auditCustomAttributeGetterFailTestProvider(): array
    {
        return [
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
        ];
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheIpAddressResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid IpAddressResolver implementation');

        $this->app['config']->set('laravel-auditing.resolver.ip_address', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheUrlResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UrlResolver implementation');

        $this->app['config']->set('laravel-auditing.resolver.url', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheUserAgentResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UserAgentResolver implementation');

        $this->app['config']->set('laravel-auditing.resolver.user_agent', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheUserResolverImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid UserResolver implementation');

        $this->app['config']->set('laravel-auditing.resolver.user', null);

        $model = new Article();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itReturnsTheAuditData()
    {
        $now = Carbon::now();

        $model = Article::factory()->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(self::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => $now->toDateTimeString(),
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'subject_id'        => null,
            'subject_type'      => null,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony',
            'tags'           => null,
        ], $auditData, true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itReturnsTheAuditDataIncludingSubjectAttributes() {
        $user = User::factory()->create();
        Subject::attach($user);
        $now = Carbon::now();

        $model = Article::factory()->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(self::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => $now->toDateTimeString(),
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'subject_id'     => $user->getKey(),
            'subject_type'   => User::class,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony',
            'user_id'        => null,
            'tags'           => null,
        ], $auditData, true);
    }

    /**
     * @dataProvider userResolverProvider
     * @test
     */
    public function itReturnsTheAuditDataIncludingUserId(
        string $guard,
        string $driver,
        ?string $id
    ) {
        $this->app['config']->set('laravel-auditing.user.guards', [$guard]);

        $user = User::factory()->create();
        $this->actingAs($user, $driver);
        $now = Carbon::now();

        $model = Article::factory()->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(self::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => $now->toDateTimeString(),
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'subject_id'     => null,
            'subject_type'   => null,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony',
            'user_id'        => $id,
            'tags'           => null,
        ], $auditData, true);
    }

    public function userResolverProvider(): array
    {
        return [
            ['api', 'web', null],
            ['web', 'api', null],
            ['api', 'api', '1'],
            ['web', 'web', '1'],
        ];
    }

    /**
     * @test
     */
    public function itReturnsTheAuditDataIncludingVirtualUserId()
    {
        $this->actingAs(new VirtualUser(), 'api');

        $now = Carbon::now();

        $model = Article::factory()->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(self::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => $now->toDateTimeString(),
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'subject_id'     => null,
            'subject_type'   => null,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony',
            'user_id'        => VirtualUser::ID,
            'tags'           => null,
        ], $auditData, true);
    }

    /**
     * @test
     */
    public function itReturnsTheAuditDataIncludingExtra()
    {
        $now = Carbon::now();

        $model = Article::factory()->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $model->setAuditEvent('created');

        $this->assertCount(self::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

        Assert::assertArraySubset([
            'extra' => [
                'year' => $now->year,
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
        ], $auditData, true);
    }

    /**
     * @test
     */
    public function itReturnsTheDefaultExtra()
    {
        $model = Article::factory()->make();
        $model->setAuditEvent('created');

        Assert::assertArraySubset([
            'extra' => null,
        ], $model->toAudit(), true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itExcludesAttributesFromTheAuditDataWhenInStrictMode()
    {
        $this->app['config']->set('laravel-auditing.strict', true);

        $model = Article::factory()->make([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
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

        $this->assertCount(self::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

        Assert::assertArraySubset([
            'old_values' => [],
            'new_values' => [
                'title'   => 'How To Audit Eloquent Models',
                'content' => 'First step: install the laravel-auditing package.',
            ],
            'event'          => 'created',
            'auditable_id'   => null,
            'auditable_type' => Article::class,
            'subject_id'        => null,
            'subject_type'      => null,
            'url'            => 'console',
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'Symfony',
            'tags'           => null,
        ], $auditData, true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itFailsWhenTheAttributeModifierImplementationIsInvalid()
    {
        $this->expectException(AuditingException::class);
        $this->expectExceptionMessage('Invalid AttributeModifier implementation: invalidAttributeRedactorOrEncoder');

        $model = Article::factory()->make();

        $model->attributeModifiers = [
            'title' => 'invalidAttributeRedactorOrEncoder',
        ];

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::toAudit
     * @test
     */
    public function itModifiesTheAuditAttributesSuccessfully()
    {
        $model = Article::factory()->make([
            'title'        => 'How To Audit Models',
            'content'      => 'N/A',
            'reviewed'     => 0,
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
            'title'    => RightRedactor::class,
            'content'  => LeftRedactor::class,
            'reviewed' => Base64Encoder::class,
        ];

        Assert::assertArraySubset([
            'old_values' => [
                'title'        => 'Ho#################',
                'content'      => '##A',
                'published_at' => null,
                'reviewed'     => 'MA==',
            ],
            'new_values' => [
                'title'        => 'How#########################',
                'content'      => '############################################kage.',
                'published_at' => $now->toDateTimeString(),
                'reviewed'     => 'MQ==',
            ],
        ], $model->toAudit(), true);
    }

    /**
     * @group Auditable::setAuditEvent
     * @group Auditable::transformAudit
     * @group Auditable::toAudit
     * @test
     */
    public function itTransformsTheAuditData()
    {
        $model = new class() extends Article {
            protected $attributes = [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => '2012-06-14 15:03:00',
            ];

            public function transformAudit(array $data): array
            {
                $data['new_values']['slug'] = Str::slug($data['new_values']['title']);

                return $data;
            }
        };

        $model->setAuditEvent('created');

        $this->assertCount(self::AUDIT_FIELDS_COUNT, $auditData = $model->toAudit());

        Assert::assertArraySubset([
            'new_values' => [
                'title'        => 'How To Audit Eloquent Models',
                'content'      => 'First step: install the laravel-auditing package.',
                'reviewed'     => 1,
                'published_at' => '2012-06-14 15:03:00',
                'slug'         => 'how-to-audit-eloquent-models',
            ],
        ], $auditData, true);
    }

    /**
     * @group Auditable::getAuditInclude
     * @test
     */
    public function itReturnsTheDefaultAttributesToBeIncludedInTheAudit()
    {
        $model = new Article();

        Assert::assertArraySubset([], $model->getAuditInclude(), true);
    }

    /**
     * @group Auditable::getAuditInclude
     * @test
     */
    public function itReturnsTheCustomAttributesToBeIncludedInTheAudit()
    {
        $model = new Article();

        $model->auditInclude = [
            'title',
            'content',
        ];

        Assert::assertArraySubset([
            'title',
            'content',
        ], $model->getAuditInclude(), true);
    }

    /**
     * @group Auditable::getAuditExclude
     * @test
     */
    public function itReturnsTheDefaultAttributesToBeExcludedFromTheAudit()
    {
        $model = new Article();

        Assert::assertArraySubset([], $model->getAuditExclude(), true);
    }

    /**
     * @group Auditable::getAuditExclude
     * @test
     */
    public function itReturnsTheCustomAttributesToBeExcludedFromTheAudit()
    {
        $model = new Article();

        $model->auditExclude = [
            'published_at',
        ];

        Assert::assertArraySubset([
            'published_at',
        ], $model->getAuditExclude(), true);
    }

    /**
     * @group Auditable::getAuditStrict
     * @test
     */
    public function itReturnsTheDefaultAuditStrictValue()
    {
        $model = new Article();

        $this->assertFalse($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditStrict
     * @test
     */
    public function itReturnsTheCustomAuditStrictValueFromAttribute()
    {
        $model = new Article();

        $model->auditStrict = true;

        $this->assertTrue($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditStrict
     * @test
     */
    public function itReturnsTheCustomAuditStrictValueFromConfig()
    {
        $this->app['config']->set('laravel-auditing.strict', true);

        $model = new Article();

        $this->assertTrue($model->getAuditStrict());
    }

    /**
     * @group Auditable::getAuditTimestamps
     * @test
     */
    public function itReturnsTheDefaultAuditTimestampsValue()
    {
        $model = new Article();

        $this->assertFalse($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditTimestamps
     * @test
     */
    public function itReturnsTheCustomAuditTimestampsValueFromAttribute()
    {
        $model = new Article();

        $model->auditTimestamps = true;

        $this->assertTrue($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditTimestamps
     * @test
     */
    public function itReturnsTheCustomAuditTimestampsValueFromConfig()
    {
        $this->app['config']->set('laravel-auditing.timestamps', true);

        $model = new Article();

        $this->assertTrue($model->getAuditTimestamps());
    }

    /**
     * @group Auditable::getAuditDriver
     * @test
     */
    public function itReturnsTheDefaultAuditDriverValue()
    {
        $model = new Article();

        $this->assertSame('database', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditDriver
     * @test
     */
    public function itReturnsTheCustomAuditDriverValueFromAttribute()
    {
        $model = new Article();

        $model->auditDriver = 'RedisDriver';

        $this->assertSame('RedisDriver', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditDriver
     * @test
     */
    public function itReturnsTheCustomAuditDriverValueFromConfig()
    {
        $this->app['config']->set('laravel-auditing.driver', 'RedisDriver');

        $model = new Article();

        $this->assertSame('RedisDriver', $model->getAuditDriver());
    }

    /**
     * @group Auditable::getAuditThreshold
     * @test
     */
    public function itReturnsTheDefaultAuditThresholdValue()
    {
        $model = new Article();

        $this->assertSame(0, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::getAuditThreshold
     * @test
     */
    public function itReturnsTheCustomAuditThresholdValueFromAttribute()
    {
        $model = new Article();

        $model->auditThreshold = 10;

        $this->assertSame(10, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::getAuditThreshold
     * @test
     */
    public function itReturnsTheCustomAuditThresholdValueFromConfig()
    {
        $this->app['config']->set('laravel-auditing.threshold', 200);

        $model = new Article();

        $this->assertSame(200, $model->getAuditThreshold());
    }

    /**
     * @group Auditable::generateTags
     * @test
     */
    public function itReturnsTheDefaultGeneratedAuditTags()
    {
        $model = new Article();

        Assert::assertArraySubset([], $model->generateTags(), true);
    }

    /**
     * @group Auditable::generateTags
     * @test
     */
    public function itReturnsTheCustomGeneratedAuditTags()
    {
        $model = new class() extends Article {
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
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditAuditableTypeDoesNotMatchTheModelType()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable type Ensi\LaravelAuditing\Tests\Models\Article, got Ensi\LaravelAuditing\Tests\Models\User instead');

        $audit = AuditFactory::new()->make([
            'auditable_type' => User::class,
        ]);

        $model = new Article();

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditAuditableTypeDoesNotMatchTheMorphMapValue()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable type articles, got users instead');

        Relation::morphMap([
            'articles' => Article::class,
        ]);

        $audit = AuditFactory::new()->make([
            'auditable_type' => 'users',
        ]);

        $model = new Article();

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditAuditableIdDoesNotMatchTheModelId()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable id 2, got 1 instead');

        $firstAudit = Article::factory()->create()->audits()->first();
        $secondModel = Article::factory()->create();

        $secondModel->transitionTo($firstAudit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditAuditableIdTypeDoesNotMatchTheModelIdType()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Expected Auditable id 1, got 1 instead');

        $model = Article::factory()->create();

        $audit = AuditFactory::new()->create([
            'auditable_type' => Article::class,
            'auditable_id'   => (string) $model->id,
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
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itTransitionsWhenTheAuditAuditableIdTypeDoesNotMatchTheModelIdType()
    {
        $model = Article::factory()->create();

        // Key depends on type
        if ($model->getKeyType() == 'string') {
            $key = (string) $model->id;
        } else {
            $key = (int) $model->id;
        }

        $audit = AuditFactory::new()->create([
            'auditable_type' => Article::class,
            'auditable_id'   => $key,
        ]);

        $this->assertInstanceOf(Auditable::class, $model->transitionTo($audit));
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenAnAttributeRedactorIsSet()
    {
        $this->expectException(AuditableTransitionException::class);
        $this->expectExceptionMessage('Cannot transition states when an AttributeRedactor is set');

        $model = Article::factory()->create();

        $model->attributeModifiers = [
            'title' => RightRedactor::class,
        ];

        $audit = AuditFactory::new()->create([
            'auditable_id'   => $model->getKey(),
            'auditable_type' => Article::class,
        ]);

        $model->transitionTo($audit);
    }

    /**
     * @group Auditable::transitionTo
     * @test
     */
    public function itFailsToTransitionWhenTheAuditableAttributeCompatibilityIsNotMet()
    {
        $model = Article::factory()->create();

        $incompatibleAudit = AuditFactory::new()->create([
            'event'          => 'created',
            'auditable_id'   => $model->getKey(),
            'auditable_type' => Article::class,
            'old_values'     => [],
            'new_values'     => [
                'subject' => 'Culpa qui rerum excepturi quisquam quia officiis.',
                'text'    => 'Magnam enim suscipit officiis tempore ut quis harum.',
            ],
        ]);

        try {
            $model->transitionTo($incompatibleAudit);
        } catch (AuditableTransitionException $e) {
            $this->assertSame(
                'Incompatibility between [Ensi\LaravelAuditing\Tests\Models\Article:1] and [Ensi\LaravelAuditing\Models\Audit:3]',
                $e->getMessage()
            );

            Assert::assertArraySubset([
                'subject',
                'text',
            ], $e->getIncompatibilities(), true);
        }
    }

    /**
     * @group Auditable::transitionTo
     * @test
     *
     * @dataProvider auditableTransitionTestProvider
     *
     * @param bool  $morphMap
     * @param array $oldValues
     * @param array $newValues
     * @param array $oldExpectation
     * @param array $newExpectation
     */
    public function itTransitionsToAnotherModelState(
        bool $morphMap,
        array $oldValues,
        array $newValues,
        array $oldExpectation,
        array $newExpectation
    ) {
        $models = Article::factory()->count(2)->create([
            'title'   => 'Facilis voluptas qui impedit deserunt vitae quidem.',
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
                'auditable_id'   => $model->getKey(),
                'auditable_type' => $auditableType,
                'old_values'     => $oldValues,
                'new_values'     => $newValues,
            ]);
        });

        // Transition with old values
        $this->assertInstanceOf(Auditable::class, $models[0]->transitionTo($audits[0], true));
        $this->assertSame($oldExpectation, $models[0]->getDirty());

        // Transition with new values
        $this->assertInstanceOf(Auditable::class, $models[1]->transitionTo($audits[1]));
        $this->assertSame($newExpectation, $models[1]->getDirty());
    }

    /**
     * @test
     */
    public function itWorksWithStringKeyModels()
    {
        $model = ApiModel::factory()->create();
        $model->save();
        $model->refresh();

        $this->assertCount(1, $model->audits);

        $model->content = 'Something else';
        $model->save();
        $model->refresh();

        $this->assertCount(2, $model->audits);
    }

    /**
     * @return array
     */
    public function auditableTransitionTestProvider(): array
    {
        return [
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
                    'title'   => 'Nullam egestas interdum eleifend.',
                    'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
                ],

                // Expectation when transitioning with old values
                [],

                // Expectation when transitioning with new values
                [
                    'title'   => 'NULLAM EGESTAS INTERDUM ELEIFEND.',
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
                    'title'   => 'Vivamus a urna et lorem faucibus malesuada nec nec magna.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // New values
                [
                    'title'   => 'Nullam egestas interdum eleifend.',
                    'content' => 'Morbi consectetur laoreet sem, eu tempus odio tempor id.',
                ],

                // Expectation when transitioning with old values
                [
                    'title'   => 'VIVAMUS A URNA ET LOREM FAUCIBUS MALESUADA NEC NEC MAGNA.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // Expectation when transitioning with new values
                [
                    'title'   => 'NULLAM EGESTAS INTERDUM ELEIFEND.',
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
                    'title'   => 'Vivamus a urna et lorem faucibus malesuada nec nec magna.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // New values
                [],

                // Expectation when transitioning with old values
                [
                    'title'   => 'VIVAMUS A URNA ET LOREM FAUCIBUS MALESUADA NEC NEC MAGNA.',
                    'content' => 'Mauris ipsum erat, semper non quam vel, sodales tincidunt ligula.',
                ],

                // Expectation when transitioning with new values
                [],
            ],
        ];
    }
}
