<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Foundation\Auth\User as FoundationUser;

class User extends FoundationUser
{
    protected $primaryKey = 'internal_id';
}
