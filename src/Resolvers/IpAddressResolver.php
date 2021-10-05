<?php

namespace Ensi\LaravelAuditing\Resolvers;

use Illuminate\Support\Facades\Request;

class IpAddressResolver implements \Ensi\LaravelAuditing\Contracts\IpAddressResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        return Request::ip();
    }
}
