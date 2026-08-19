<?php

namespace Illuminate\Tests\App\Models\Keys;

use Illuminate\Foundation\Auth\User as FoundationUser;

class UserWithInternalIdKey extends FoundationUser
{
    protected $table = 'users';
    protected $primaryKey = 'internal_id';
}
