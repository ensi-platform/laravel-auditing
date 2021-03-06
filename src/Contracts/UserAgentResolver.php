<?php

namespace Ensi\LaravelEnsiAudit\Contracts;

interface UserAgentResolver
{
    /**
     * Resolve the User Agent.
     *
     * @return string|null
     */
    public static function resolve();
}
