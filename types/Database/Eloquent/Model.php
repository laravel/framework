<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use function PHPStan\Testing\assertType;

class User extends Authenticatable
{
}

assertType('Illuminate\Database\Eloquent\Collection<int, User>', User::all());

assertType('Illuminate\Database\Eloquent\Collection<int, User>', User::newCollection([new User]));

assertType('Illuminate\Database\Eloquent\Builder<User>', User::on());

assertType('Illuminate\Database\Eloquent\Builder<User>', User::with('string'));

assertType('int', User::destroy(collect([1]));

assertType('Illuminate\Database\Eloquent\Builder<User>', User::query());

assertType('Illuminate\Database\Eloquent\Builder<User>', User::newQuery());

assertType('Illuminate\Database\Eloquent\Builder<User>', User::newQueryWithoutRelationships());

assertType('Illuminate\Database\Eloquent\Builder<User>', User::registerGlobalScopes(function ($builder) {
    // ..
}));

assertType('Illuminate\Database\Eloquent\Builder<User>', User::newQueryWithoutScopes());

assertType('Illuminate\Database\Eloquent\Builder<User>', User::newQueryWithoutScope('string'));

assertType('Illuminate\Database\Eloquent\Builder<User>', User::newQueryForRestoration(2));

assertType('Illuminate\Database\Eloquent\Builder<User>', User::newEloquentBuilder(function ($builder) {
    // ..
}));

assertType('Illuminate\Database\Eloquent\Collection<int, User>', User::hydrate([[
    'string' => 'string'
]]));

assertType('Illuminate\Database\Eloquent\Collection<int, User>', User::fromQuery('string'));

// assertType('Illuminate\Database\Eloquent\Collection<int, User>|User|null', User::find(1));

assertType('Illuminate\Database\Eloquent\Collection<int, User>',  User::findMany([1]));

// assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', User::findOrFail(1));

// assertType('Illuminate\Database\Eloquent\Collection<int, User>',  User::get());

assertType('Illuminate\Support\Collection<int, mixed>',  User::pluck('string'));

assertType('Illuminate\Support\Collection<int, string>',  User::chunkMap(function ($user) {
    // assertType('User', $user);

    return 'string';
}));


