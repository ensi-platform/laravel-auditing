<?php

namespace Ensi\LaravelEnsiAudit\Resolvers;

use Illuminate\Support\Facades\Request;

class IpAddressResolver implements \Ensi\LaravelEnsiAudit\Contracts\IpAddressResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        return Request::ip();
    }
}
