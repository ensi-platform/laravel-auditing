<?php

namespace Ensi\LaravelAuditing\Tests\Data\Models;

use Carbon\CarbonInterface;
use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\SupportsAudit;
use Ensi\LaravelAuditing\Tests\Data\Models\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CarbonInterface|null $published_at
 */
class Article extends Model implements Auditable
{
    use SupportsAudit;
    use SoftDeletes;

    public const AUDIT_FIELDS_COUNT = 14;

    public const RESOLVE_FIELDS_COUNT = 20;

    public const AUDIT_META_FIELDS_COUNT = 16;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'reviewed' => 'bool',
        'published_at' => 'datetime',
    ];

    /**
     * {@inheritdoc}
     */
    protected $dates = [
        'published_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'title',
        'content',
        'published_at',
        'reviewed',
    ];

    /**
     * Uppercase Title accessor.
     *
     * @param string $value
     *
     * @return string
     */
    public function getTitleAttribute(string $value): string
    {
        return strtoupper($value);
    }

    public function getAuditExtra(): ?array
    {
        return $this->published_at
            ? ['year' => $this->published_at->year]
            : null;
    }

    public static function factory(): ArticleFactory
    {
        return ArticleFactory::new();
    }
}
