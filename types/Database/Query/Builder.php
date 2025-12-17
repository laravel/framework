<?php

namespace Illuminate\Types\Query\Builder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

use function PHPStan\Testing\assertType;

/** @param \Illuminate\Database\Eloquent\Builder<\User> $userQuery */
function test(Builder $query, EloquentBuilder $userQuery): void
{
    assertType('stdClass|null', $query->first());
    assertType('stdClass|null', $query->find(1));
    assertType('42|stdClass', $query->findOr(1, fn () => 42));
    assertType('42|stdClass', $query->findOr(1, callback: fn () => 42));
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
    assertType('Illuminate\Support\LazyCollection<int, stdClass>', $query->lazy());
    assertType('Illuminate\Support\LazyCollection<int, stdClass>', $query->lazyById());
    assertType('Illuminate\Support\LazyCollection<int, stdClass>', $query->lazyByIdDesc());

    $query->chunk(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, stdClass>', $users);
        assertType('int', $page);
    });
    $query->chunkById(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, stdClass>', $users);
        assertType('int', $page);
    });
    $query->chunkMap(function ($users) {
        assertType('stdClass', $users);
    });
    $query->chunkByIdDesc(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, stdClass>', $users);
        assertType('int', $page);
    });
    $query->each(function ($users, $page) {
        assertType('stdClass', $users);
        assertType('int', $page);
    });
    $query->eachById(function ($users, $page) {
        assertType('stdClass', $users);
        assertType('int', $page);
    });
    assertType('Illuminate\Database\Query\Builder', $query->pipe(function () {
        //
    }));
    assertType('Illuminate\Database\Query\Builder', $query->pipe(fn () => null));
    assertType('Illuminate\Database\Query\Builder', $query->pipe(fn ($query) => $query));
    assertType('5', $query->pipe(fn ($query) => 5));
}
