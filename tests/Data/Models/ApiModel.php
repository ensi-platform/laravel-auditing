<?php

namespace Ensi\LaravelAuditing\Tests\Data\Models;

use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\SupportsAudit;
use Ensi\LaravelAuditing\Tests\Data\Models\Factories\ApiModelFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiModel extends Model implements Auditable
{
    use SupportsAudit;
    use SoftDeletes;

    /**
     * @var string UUID key
     */
    public $primaryKey = 'api_model_id';

    /**
     * @var bool Set to false for UUID keys
     */
    public $incrementing = false;

    /**
     * @var string Set to string for UUID keys
     */
    protected $keyType = 'string';

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
        'api_model_id',
        'content',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public static function factory(): ApiModelFactory
    {
        return ApiModelFactory::new();
    }
}
