<?php

namespace Greensight\LaravelAuditing\Resolvers;

use Greensight\LaravelAuditing\Contracts\Principal;
use Illuminate\Support\Optional;

class SubjectManager
{
    /** @var Principal|null */
    private $principal;

    /**
     * @return Optional|Principal
     */
    public function current(): Optional
    {
        return new Optional($this->principal);
    }

    public function attach(Principal $subject): void
    {
        $this->principal = $subject;
    }

    public function detach(): void
    {
        $this->principal = null;
    }
}
