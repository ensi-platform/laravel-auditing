<?php

namespace Ensi\LaravelEnsiAudit\Tests\Models;

use Ensi\LaravelEnsiAudit\Database\Factories\ArticleFactory;
use Ensi\LaravelEnsiAudit\SupportsAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ensi\LaravelEnsiAudit\Contracts\Auditable;

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

    public static function factory(): ArticleFactory
    {
        return ArticleFactory::new();
    }
}
