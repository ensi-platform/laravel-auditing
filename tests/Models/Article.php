<?php

namespace Ensi\LaravelAuditing\Tests\Models;

use Carbon\CarbonInterface;
use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\Database\Factories\ArticleFactory;
use Ensi\LaravelAuditing\SupportsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CarbonInterface|null $published_at
 */
class Article extends Model implements Auditable
{
    use SupportsAudit;
    use SoftDeletes;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'reviewed' => 'bool',
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
