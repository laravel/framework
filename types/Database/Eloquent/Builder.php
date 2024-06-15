<?php

namespace Illuminate\Types\Builder;

use Illuminate\Database\Eloquent\Builder;

use function PHPStan\Testing\assertType;

/** @param \Illuminate\Database\Eloquent\Builder<\User> $query */
function test(Builder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->where('id', 1));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->orWhere('name', 'John'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->whereNot('status', 'active'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->with('relation'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->without('relation'));
    assertType('Illuminate\Database\Eloquent\Builder<User>', $query->withOnly(['relation']));

    assertType('User|null', $query->first());
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->get());
    assertType('Illuminate\Database\Eloquent\Collection<int, User>|User|null', $query->find(1));
    assertType('Illuminate\Database\Eloquent\Collection<int, User>', $query->findMany([1, 2, 3]));
    assertType('Illuminate\Database\Eloquent\Collection<int, User>|User', $query->findOrFail(1));
    assertType('User', $query->firstOrNew(['id' => 1]));
    assertType('User', $query->firstOrCreate(['id' => 1]));
    assertType('User', $query->create(['name' => 'John']));
    assertType('User', $query->forceCreate(['name' => 'John']));
    assertType('User', $query->updateOrCreate(['id' => 1], ['name' => 'John']));
    assertType('User', $query->firstOrFail());
    assertType('User', $query->sole());

    assertType('Illuminate\Database\Query\Builder', $query->toBase());
    assertType('object|null', $query->toBase()->first());
}
