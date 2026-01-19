<?php

namespace Illuminate\Auth;

use Illuminate\Auth\Eloquent\EloquentAuthenticatable;

/**
 * @deprecated Use \Illuminate\Auth\Eloquent\EloquentAuthenticatable instead. Will be removed in a future Laravel version.
 */
trait Authenticatable
{
    use EloquentAuthenticatable;
}
