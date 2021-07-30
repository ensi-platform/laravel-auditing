<?php

namespace Greensight\LaravelAuditing\Resolvers;

use Illuminate\Support\Facades\Request;

class IpAddressResolver implements \Greensight\LaravelAuditing\Contracts\IpAddressResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        return Request::ip();
    }
}
