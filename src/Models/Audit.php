<?php

namespace Ensi\LaravelAuditing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event
 * @property array $old_values
 * @property array $new_values
 * @property array $state
 * @property string $url
 * @property string $ip_address
 * @property string $user_agent
 * @property string $tags
 *
 * @property string $auditable_type
 * @property int $auditable_id          Измененная сущность
 * @property string $root_entity_type
 * @property int $root_entity_id        Корневая сущность
 * @property string $subject_type
 * @property int $subject_id            Субъект доступа
 * @property string|null $user_id       Идентификатор пользователя
 *
 * @property \Carbon\CarbonInterface $created_at
 * @property \Carbon\CarbonInterface $updated_at
 * @property string $transaction_uid
 * @property \Carbon\CarbonInterface $transaction_time
 *
 * @property \Illuminate\Database\Eloquent\Model|\Ensi\LaravelAuditing\Contracts\Principal|null $subject
 * @property \Illuminate\Database\Eloquent\Model|\Ensi\LaravelAuditing\Contracts\Auditable $auditable
 * @property \Illuminate\Database\Eloquent\Model|null $root
 *
 * @method static static create(array $attributes)
 * @method static \Illuminate\Database\Eloquent\Builder|static forRoot(\Illuminate\Database\Eloquent\Model $root)
 */
class Audit extends Model implements \Ensi\LaravelAuditing\Contracts\Audit
{
    use \Ensi\LaravelAuditing\Audit;

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'old_values'   => 'json',
        'new_values'   => 'json',
        // Note: Please do not add 'auditable_id' in here, as it will break non-integer PK models
        'state'        => 'json',
    ];

    protected $dates = ['transaction_time'];

    /** @var string Формат дат для БД с точностью до микросекунд */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    public function scopeForRoot(Builder $query, Model $root): Builder
    {
        return $query->where('root_entity_type', $root->getMorphClass())
            ->where('root_entity_id', $root->getKey());
    }
}
