<?php

namespace Ensi\LaravelEnsiAudit\Tests\Models;

use Ensi\LaravelEnsiAudit\Database\Factories\UserFactory;
use Ensi\LaravelEnsiAudit\SupportsAudit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Ensi\LaravelEnsiAudit\Contracts\Auditable;

class User extends Model implements Auditable, Authenticatable
{
    use \Illuminate\Auth\Authenticatable;
    use SupportsAudit;

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'is_admin' => 'bool',
    ];

    /**
     * Uppercase first name character accessor.
     *
     * @param string $value
     *
     * @return string
     */
    public function getFirstNameAttribute(string $value): string
    {
        return ucfirst($value);
    }

    public static function factory(): UserFactory
    {
        return UserFactory::new();
    }
}
