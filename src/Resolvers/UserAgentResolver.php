<?php

namespace Ensi\LaravelEnsiAudit\Resolvers;

use Illuminate\Support\Facades\Request;

class UserAgentResolver implements \Ensi\LaravelEnsiAudit\Contracts\UserAgentResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return Request::header('User-Agent');
    }
}
