<?php

namespace Illuminate\Tests\Integration\Auth\Fixtures\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AuthenticationTestUser extends Authenticatable
{
    public $table = 'users';
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
