<?php

namespace Greensight\LaravelAuditing\Resolvers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

class UrlResolver implements \Greensight\LaravelAuditing\Contracts\UrlResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        if (App::runningInConsole()) {
            return 'console';
        }

        return Request::fullUrl();
    }
}
