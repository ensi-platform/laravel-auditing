<?php

namespace Ensi\LaravelEnsiAudit\Facades;

use Ensi\LaravelEnsiAudit\Resolvers\SubjectManager;
use Ensi\LaravelEnsiAudit\Contracts\Principal;
use Illuminate\Support\Optional;
use Illuminate\Support\Facades\Facade;
use Ensi\LaravelEnsiAudit\Contracts\UserResolver;

/**
 * @method static void attach(\Ensi\LaravelEnsiAudit\Contracts\Principal $subject)
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
