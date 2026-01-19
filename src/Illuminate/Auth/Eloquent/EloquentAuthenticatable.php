<?php

namespace Illuminate\Auth\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 */
trait EloquentAuthenticatable
{
    use EloquentIdentifiable, EloquentHasRememberToken, EloquentHasAuthPassword;
}
