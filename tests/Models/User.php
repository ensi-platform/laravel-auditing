<?php

namespace Greensight\LaravelAuditing\Tests\Models;

use Greensight\LaravelAuditing\Contracts\Auditable;
use Greensight\LaravelAuditing\Contracts\Principal;
use Greensight\LaravelAuditing\Database\Factories\UserFactory;
use Greensight\LaravelAuditing\SupportsAudit;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements Auditable, Principal
{
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
