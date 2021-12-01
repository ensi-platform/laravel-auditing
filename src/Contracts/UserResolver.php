<?php

namespace Ensi\LaravelAuditing\Contracts;

interface UserResolver
{
    /**
     * Resolve the User.
     *
     * @return string|null
     */
    public static function resolve();
}
