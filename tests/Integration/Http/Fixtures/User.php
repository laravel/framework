<?php

namespace Illuminate\Tests\Integration\Http\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public $quota = 10;

    public function quotaLimitFunction()
    {
        return 20;
    }
}
