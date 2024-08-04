<?php

namespace Illuminate\Types\Query\Builder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

use function PHPStan\Testing\assertType;

/** @param \Illuminate\Database\Eloquent\Builder<\User> $userQuery */
function test(Builder $query, EloquentBuilder $userQuery): void
{
    assertType('object|null', $query->first());
    assertType('object|null', $query->find(1));
    assertType('int|object', $query->findOr(1, fn () => 42));
    assertType('int|object', $query->findOr(1, callback: fn () => 42));
    assertType('Illuminate\Database\Query\Builder', $query->selectSub($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder', $query->fromSub($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder', $query->from($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder', $query->joinSub($userQuery, 'alias', 'foo'));
    assertType('Illuminate\Database\Query\Builder', $query->joinLateral($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder', $query->leftJoinLateral($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder', $query->leftJoinSub($userQuery, 'alias', 'foo'));
    assertType('Illuminate\Database\Query\Builder', $query->rightJoinSub($userQuery, 'alias', 'foo'));
    assertType('Illuminate\Database\Query\Builder', $query->crossJoinSub($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder', $query->whereExists($userQuery));
    assertType('Illuminate\Database\Query\Builder', $query->orWhereExists($userQuery));
    assertType('Illuminate\Database\Query\Builder', $query->whereNotExists($userQuery));
    assertType('Illuminate\Database\Query\Builder', $query->orWhereNotExists($userQuery));
    assertType('Illuminate\Database\Query\Builder', $query->orderBy($userQuery));
    assertType('Illuminate\Database\Query\Builder', $query->orderByDesc($userQuery));
    assertType('Illuminate\Database\Query\Builder', $query->union($userQuery));
    assertType('Illuminate\Database\Query\Builder', $query->unionAll($userQuery));
    assertType('int', $query->insertUsing([], $userQuery));
    assertType('int', $query->insertOrIgnoreUsing([], $userQuery));

    $query->chunk(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, object>', $users);
        assertType('int', $page);
    });
    $query->chunkById(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, object>', $users);
        assertType('int', $page);
    });
    $query->chunkMap(function ($users) {
        assertType('object', $users);
    });
    $query->chunkByIdDesc(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, object>', $users);
        assertType('int', $page);
    });
    $query->each(function ($users, $page) {
        assertType('object', $users);
        assertType('int', $page);
    });
    $query->eachById(function ($users, $page) {
        assertType('object', $users);
        assertType('int', $page);
    });
}
