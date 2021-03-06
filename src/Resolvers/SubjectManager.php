<?php

namespace Ensi\LaravelEnsiAudit\Resolvers;

use Ensi\LaravelEnsiAudit\Contracts\Principal;
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
