<?php

namespace Illuminate\Types\Scope;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

use function PHPStan\Testing\assertType;

/**
 * @implements Scope<User>
 */
class UserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<covariant Illuminate\Types\Scope\User>', $builder);
        assertType('Illuminate\Types\Scope\User', $model);
    }
}

/**
 * @implements Scope<Model>
 */
class GenericScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<covariant Illuminate\Database\Eloquent\Model>', $builder);
        assertType('Illuminate\Database\Eloquent\Model', $model);
    }
}

class User extends Model
{
}

$user = new User();
$query = User::query();
new UserScope()->apply($query, $user);
new GenericScope()->apply($query, $user);
