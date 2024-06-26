<?php

namespace Ensi\LaravelAuditing\Tests\Data\Models;

use Illuminate\Contracts\Auth\Authenticatable;

class VirtualUser implements Authenticatable
{
    public const ID = 'admin:12345';

    public function getAuthIdentifierName()
    {
    }

    public function getAuthIdentifier()
    {
        return self::ID;
    }

    public function getAuthPassword()
    {
    }

    public function getRememberToken()
    {
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }
}
