<?php

namespace Ensi\LaravelAuditing\Resolvers;

use Ensi\LaravelAuditing\Contracts\Principal;
use Illuminate\Support\Optional;

class SubjectManager
{
    private ?Principal $principal = null;

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
