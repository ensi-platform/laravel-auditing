<?php

namespace Ensi\LaravelAuditing\Facades;

use Ensi\LaravelAuditing\Resolvers\SubjectManager;
use Ensi\LaravelAuditing\Contracts\Principal;
use Illuminate\Support\Optional;
use Illuminate\Support\Facades\Facade;
use Ensi\LaravelAuditing\Contracts\UserResolver;

/**
 * @method static void attach(\Ensi\LaravelAuditing\Contracts\Principal $subject)
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
