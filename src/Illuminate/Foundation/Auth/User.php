<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Auth\Eloquent\EloquentAuthenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\Identity\StatefulIdentifiable as StatefulIdentifiableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

class User extends Model implements
    StatefulIdentifiableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use EloquentAuthenticatable, Authorizable, CanResetPassword, MustVerifyEmail;
}
