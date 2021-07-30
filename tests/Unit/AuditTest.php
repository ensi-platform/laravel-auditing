<?php

namespace Greensight\LaravelAuditing\Tests\Unit;

use Carbon\Carbon;
use DateTimeInterface;
use Greensight\LaravelAuditing\Database\Factories\AuditFactory;
use Greensight\LaravelAuditing\Encoders\Base64Encoder;
use Greensight\LaravelAuditing\Facades\Subject;
use Greensight\LaravelAuditing\Redactors\LeftRedactor;
use Greensight\LaravelAuditing\Tests\AuditingTestCase;
use Greensight\LaravelAuditing\Tests\Models\Article;
use Greensight\LaravelAuditing\Tests\Models\User;
use Illuminate\Foundation\Testing\Concerns\InteractsWithTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Assert;

class AuditTest extends AuditingTestCase
{
    use InteractsWithTime;

    /**
     * @group Audit::resolveData
     * @test
     */
    public function itResolvesAuditData()
    {
        $now = Carbon::now();
        $article = null;

        $article = Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $audit = $article->audits()->first();

        $this->assertCount(18, $resolvedData = $audit->resolveData());

        Assert::assertArraySubset([
            'audit_id'         => 1,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags'       => null,
            'audit_created_at' => $audit->created_at->toJSON(),
            'audit_updated_at' => $audit->updated_at->toJSON(),
            'new_title'        => 'How To Audit Eloquent Models',
            'new_content'      => 'First step: install the laravel-auditing package.',
            'new_published_at' => $now->toDateTimeString(),
            'new_reviewed'     => 1,
        ], $resolvedData, true);
    }

    /**
     * @group Audit::resolveData
     * @test
     */
    public function itResolvesAuditDataIncludingSubjectAttributes()
    {
        $now = Carbon::now();

        $user = User::factory()->create([
            'is_admin'   => 1,
            'first_name' => 'rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);

        Subject::attach($user);

        $article = Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ]);

        $audit = $article->audits()->first();

        $this->assertCount(20, $resolvedData = $audit->resolveData());

        Assert::assertArraySubset([
            'audit_id'         => 2,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags'       => null,
            'audit_created_at' => $audit->created_at->toJSON(),
            'audit_updated_at' => $audit->updated_at->toJSON(),
            'new_title'        => 'How To Audit Eloquent Models',
            'new_content'      => 'First step: install the laravel-auditing package.',
            'new_published_at' => $now->toDateTimeString(),
            'new_reviewed'     => 1,
            'subject_id'       => (string)$user->getKey(),
        ], $resolvedData, true);
    }

    /**
     * @group Audit::resolveData
     * @group Audit::getDataValue
     * @test
     */
    public function itReturnsTheAppropriateAuditableDataValues()
    {
        $user = User::factory()->create([
            'is_admin'   => 1,
            'first_name' => 'rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);

        $audit = Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => Carbon::now(),
        ])->audits()->first();

        // Resolve data, making it available to the getDataValue() method
        $this->assertCount(18, $audit->resolveData());

        // Mutate value
        $this->assertSame('HOW TO AUDIT ELOQUENT MODELS', $audit->getDataValue('new_title'));

        // Cast value
        $this->assertTrue($audit->getDataValue('new_reviewed'));

        // Date value
        $this->assertInstanceOf(DateTimeInterface::class, $audit->getDataValue('new_published_at'));

        // Original value
        $this->assertSame('First step: install the laravel-auditing package.', $audit->getDataValue('new_content'));

        // Invalid value
        $this->assertNull($audit->getDataValue('invalid_key'));
    }

    /**
     * @group Audit::getMetadata
     * @test
     */
    public function itReturnsAuditMetadataAsArray()
    {
        $audit = Article::factory()->create()->audits()->first();

        $this->assertCount(14, $metadata = $audit->getMetadata());

        Assert::assertArraySubset([
            'audit_id'         => 1,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags'       => null,
            'audit_created_at' => $audit->created_at->toJSON(),
            'audit_updated_at' => $audit->updated_at->toJSON(),
        ], $metadata, true);
    }

    /**
     * @group Audit::getMetadata
     * @test
     */
    public function itReturnsAuditMetadataIncludingSubjectAttributesAsArray()
    {
        $user = User::factory()->create([
            'is_admin'   => 1,
            'first_name' => 'rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);

        Subject::attach($user);

        $audit = Article::factory()->create()->audits()->first();

        $this->assertCount(16, $metadata = $audit->getMetadata());

        Assert::assertArraySubset([
            'audit_id'         => 2,
            'audit_event'      => 'created',
            'audit_url'        => 'console',
            'audit_ip_address' => '127.0.0.1',
            'audit_user_agent' => 'Symfony',
            'audit_tags'       => null,
            'audit_created_at' => $audit->created_at->toJSON(),
            'audit_updated_at' => $audit->updated_at->toJSON(),
            'subject_id'       => (string)$user->getKey(),
        ], $metadata, true);
    }

    /**
     * @group Audit::getMetadata
     * @test
     */
    public function itReturnsAuditMetadataAsJsonString()
    {
        $this->travel(-1)->minutes();
        $now = now()->toJSON();

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
    "transaction_time": "$now"
}
EOF;

        $this->assertSame($expected, $metadata);
    }

    /**
     * @group Audit::getMetadata
     * @test
     */
    public function itReturnsAuditMetadataIncludingSubjectAttributesAsJsonString()
    {
        $user = User::factory()->create([
            'is_admin'   => 1,
            'first_name' => 'rick',
            'last_name'  => 'Sanchez',
            'email'      => 'rick@wubba-lubba-dub.dub',
        ]);

        Subject::attach($user);

        $this->travel(-1)->minutes();
        $now = now()->toJSON();
        $userId = $user->getKey();

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
    "subject_id": "$userId",
    "subject_type": "Greensight\\\\LaravelAuditing\\\\Tests\\\\Models\\\\User",
    "transaction_uid": null,
    "transaction_time": "$now",
    "subject_name": "Rick",
    "user_id": $userId
}
EOF;

        $this->assertSame($expected, $metadata);
    }

    /**
     * @group Audit::getModified
     * @test
     */
    public function itReturnsAuditableModifiedAttributesAsArray()
    {
        $now = Carbon::now()->milliseconds(0);

        $audit = Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
            'published_at' => $now,
        ])->audits()->first();

        $this->assertCount(4, $modified = $audit->getModified());

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
    }

    /**
     * @group Audit::getModified
     * @test
     */
    public function itReturnsAuditableModifiedAttributesAsJsonString()
    {
        $now = Carbon::now()->milliseconds(0);
        $publishedAt = $now->toJSON();

        $audit = Article::factory()->create([
            'title'        => 'How To Audit Eloquent Models',
            'content'      => 'First step: install the laravel-auditing package.',
            'reviewed'     => 1,
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

        $this->assertSame($expected, $modified);
    }

    /**
     * @group Audit::getModified
     * @test
     */
    public function itReturnsDecodedAuditableAttributes()
    {
        $article = new class() extends Article {
            protected $table = 'articles';

            protected $attributeModifiers = [
                'title'   => Base64Encoder::class,
                'content' => LeftRedactor::class,
            ];
        };

        // Audit with redacted/encoded attributes
        $audit = AuditFactory::new()->create([
            'auditable_type' => get_class($article),
            'old_values'     => [
                'title'    => 'SG93IFRvIEF1ZGl0IE1vZGVscw==',
                'content'  => '##A',
                'reviewed' => 0,
            ],
            'new_values'     => [
                'title'    => 'SG93IFRvIEF1ZGl0IEVsb3F1ZW50IE1vZGVscw==',
                'content'  => '############################################kage.',
                'reviewed' => 1,
            ],
        ]);

        $this->assertCount(3, $modified = $audit->getModified());

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
    }

    /**
     * @group Audit::getTags
     * @test
     */
    public function itReturnsTags()
    {
        $audit = AuditFactory::new()->create([
            'tags' => 'foo,bar,baz',
        ]);

        $this->assertIsArray($audit->getTags());
        Assert::assertArraySubset([
            'foo',
            'bar',
            'baz',
        ], $audit->getTags(), true);
    }

    /**
     * @group Audit::getTags
     * @test
     */
    public function itReturnsEmptyTags()
    {
        $audit = AuditFactory::new()->create([
            'tags' => null,
        ]);

        $this->assertIsArray($audit->getTags());
        $this->assertEmpty($audit->getTags());
    }
}
