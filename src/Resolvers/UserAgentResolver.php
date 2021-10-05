<?php

namespace Ensi\LaravelAuditing\Resolvers;

use Illuminate\Support\Facades\Request;

class UserAgentResolver implements \Ensi\LaravelAuditing\Contracts\UserAgentResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return Request::header('User-Agent');
    }
}
