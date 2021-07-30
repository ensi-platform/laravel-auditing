<?php

namespace Greensight\LaravelAuditing\Resolvers;

use Illuminate\Support\Facades\Request;

class UserAgentResolver implements \Greensight\LaravelAuditing\Contracts\UserAgentResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return Request::header('User-Agent');
    }
}
