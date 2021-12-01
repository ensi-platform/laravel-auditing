<?php

namespace Ensi\LaravelAuditing\Tests\Models;

use Ensi\LaravelAuditing\Contracts\Auditable;
use Ensi\LaravelAuditing\Contracts\Principal;
use Ensi\LaravelAuditing\Database\Factories\UserFactory;
use Ensi\LaravelAuditing\SupportsAudit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements Auditable, Principal, Authenticatable
{
    use SupportsAudit;
    use \Illuminate\Auth\Authenticatable;

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

    public function getName(): string
    {
        return $this->first_name;
    }

    public function getUserIdentifier(): ?int
    {
        return $this->getKey();
    }

    public function getAuthIdentifier(): int
    {
        return $this->getKey();
    }

    public static function factory(): UserFactory
    {
        return UserFactory::new();
    }
}
