<?php

namespace Illuminate\Tests\Auth\Fixtures;

class AuthTokenGuardTestUser
{
    public $id;

    public function getAuthIdentifier()
    {
        return $this->id;
    }
}
