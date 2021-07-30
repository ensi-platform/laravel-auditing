<?php

namespace Greensight\LaravelAuditing\Facades;

use Greensight\LaravelAuditing\Resolvers\SubjectManager;
use Greensight\LaravelAuditing\Contracts\Principal;
use Illuminate\Support\Optional;
use Illuminate\Support\Facades\Facade;
use Greensight\LaravelAuditing\Contracts\UserResolver;

/**
 * @method static void attach(\Greensight\LaravelAuditing\Contracts\Principal $subject)
 * @method static void detach()
 */
class Subject extends Facade implements UserResolver
{
    protected static function getFacadeAccessor(): string
    {
        return SubjectManager::class;
    }

    /**
     * @return Optional|Principal
     */
    public static function resolve(): Optional
    {
        return static::getFacadeRoot()->current();
    }
}
