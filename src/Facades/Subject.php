<?php

namespace Ensi\LaravelAuditing\Facades;

use Ensi\LaravelAuditing\Resolvers\SubjectManager;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Optional;

/**
 * @method static void attach(\Ensi\LaravelAuditing\Contracts\Principal $subject)
 * @method static void detach()
 */
class Subject extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SubjectManager::class;
    }

    public static function resolve(): Optional
    {
        return static::getFacadeRoot()->current();
    }
}
