<?php

namespace Ensi\LaravelAuditing;

use DateTimeInterface;
use Ensi\LaravelAuditing\Contracts\AttributeEncoder;
use Ensi\LaravelAuditing\Contracts\Principal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

trait Audit
{
    /**
     * Audit data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * The Audit attributes that belong to the metadata.
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * The Auditable attributes that were modified.
     *
     * @var array
     */
    protected $modified = [];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function root(): MorphTo
    {
        return $this->morphTo('root_entity');
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionName()
    {
        return Config::get('laravel-auditing.drivers.database.connection');
    }

    /**
     * {@inheritdoc}
     */
    public function getTable(): string
    {
        return Config::get('laravel-auditing.drivers.database.table', parent::getTable());
    }

    /**
     * {@inheritdoc}
     */
    public function resolveData(): array
    {
        // Metadata
        $this->data = [
            'audit_id' => $this->id,
            'audit_event' => $this->event,
            'audit_url' => $this->url,
            'audit_ip_address' => $this->ip_address,
            'audit_user_agent' => $this->user_agent,
            'audit_tags' => $this->tags,
            'audit_created_at' => $this->serializeDate($this->created_at),
            'audit_updated_at' => $this->serializeDate($this->updated_at),
            'root_entity_id' => $this->getAttribute('root_entity_id'),
            'root_entity_type' => $this->getAttribute('root_entity_type'),
            'subject_id' => $this->getAttribute('subject_id'),
            'subject_type' => $this->getAttribute('subject_type'),
            'transaction_uid' => $this->getAttribute('transaction_uid'),
            'transaction_time' => $this->serializeDate($this->transaction_time ?? now()),
            'user_id' => $this->user_id,
            'extra' => $this->extra,
        ];

        if ($this->subject && ($this->subject instanceof Principal)) {
            $this->data['subject_name'] = $this->subject->getName();

            if (!isset($this->data['user_id'])) {
                $this->data['user_id'] = $this->subject->getUserIdentifier();
            }
        }

        $this->metadata = array_keys($this->data);

        // Modified Auditable attributes
        foreach ($this->new_values as $key => $value) {
            $this->data['new_' . $key] = $value;
        }

        foreach ($this->old_values as $key => $value) {
            $this->data['old_' . $key] = $value;
        }

        $this->modified = array_diff_key(array_keys($this->data), $this->metadata);

        return $this->data;
    }

    /**
     * Get the formatted value of an Eloquent model.
     *
     * @param Model  $model
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function getFormattedValue(Model $model, string $key, $value)
    {
        // Apply defined get mutator
        if ($model->hasGetMutator($key)) {
            return $model->mutateAttribute($key, $value);
        }

        // Cast to native PHP type
        if ($model->hasCast($key)) {
            return $model->castAttribute($key, $value);
        }

        // Honour DateTime attribute
        if ($value !== null && in_array($key, $model->getDates(), true)) {
            return $model->asDateTime($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataValue(string $key)
    {
        if (!array_key_exists($key, $this->data)) {
            return null;
        }

        $value = $this->data[$key];

        // User value
        if ($this->subject && Str::startsWith($key, 'subject_')) {
            return $this->getFormattedValue($this->subject, substr($key, 5), $value);
        }

        // Auditable value
        if ($this->auditable && Str::startsWith($key, ['new_', 'old_'])) {
            $attribute = substr($key, 4);

            return $this->getFormattedValue(
                $this->auditable,
                $attribute,
                $this->decodeAttributeValue($this->auditable, $attribute, $value)
            );
        }

        return $value;
    }

    /**
     * Decode attribute value.
     *
     * @param Contracts\Auditable $auditable
     * @param string              $attribute
     * @param mixed               $value
     *
     * @return mixed
     */
    protected function decodeAttributeValue(Contracts\Auditable $auditable, string $attribute, $value)
    {
        $attributeModifiers = $auditable->getAttributeModifiers();

        if (!array_key_exists($attribute, $attributeModifiers)) {
            return $value;
        }

        $attributeDecoder = $attributeModifiers[$attribute];

        if (is_subclass_of($attributeDecoder, AttributeEncoder::class)) {
            return call_user_func([$attributeDecoder, 'decode'], $value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(bool $json = false, int $options = 0, int $depth = 512)
    {
        if (empty($this->data)) {
            $this->resolveData();
        }

        $metadata = [];

        foreach ($this->metadata as $key) {
            $value = $this->getDataValue($key);

            $metadata[$key] = $value instanceof DateTimeInterface
                ? $this->serializeDate($value)
                : $value;
        }

        return $json ? json_encode($metadata, $options, $depth) : $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getModified(bool $json = false, int $options = 0, int $depth = 512)
    {
        if (empty($this->data)) {
            $this->resolveData();
        }

        $modified = [];

        foreach ($this->modified as $key) {
            $attribute = substr($key, 4);
            $state = substr($key, 0, 3);

            $value = $this->getDataValue($key);

            $modified[$attribute][$state] = $value instanceof DateTimeInterface
                ? $this->serializeDate($value)
                : $value;
        }

        return $json ? json_encode($modified, $options, $depth) : $modified;
    }

    /**
     * Get the Audit tags as an array.
     *
     * @return array
     */
    public function getTags(): array
    {
        return preg_split('/,/', $this->tags, flags: PREG_SPLIT_NO_EMPTY);
    }
}
