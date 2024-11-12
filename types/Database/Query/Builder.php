<?php

namespace Illuminate\Types\Query\Builder;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

use function PHPStan\Testing\assertType;

/**
 * @param  \Illuminate\Database\Query\Builder<\PDO::FETCH_OBJ>  $query
 * @param  \Illuminate\Database\Eloquent\Builder<\User>  $userQuery
 */
function testWithFetchObj(Builder $query, EloquentBuilder $userQuery): void
{
    assertType('object|null', $query->first());
    assertType('object|null', $query->find(1));
    assertType('int|object', $query->findOr(1, fn () => 42));
    assertType('int|object', $query->findOr(1, callback: fn () => 42));
    assertType('Illuminate\Database\Query\Builder<5>', $query->selectSub($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<5>', $query->fromSub($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<5>', $query->from($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<5>', $query->joinSub($userQuery, 'alias', 'foo'));
    assertType('Illuminate\Database\Query\Builder<5>', $query->joinLateral($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<5>', $query->leftJoinLateral($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<5>', $query->leftJoinSub($userQuery, 'alias', 'foo'));
    assertType('Illuminate\Database\Query\Builder<5>', $query->rightJoinSub($userQuery, 'alias', 'foo'));
    assertType('Illuminate\Database\Query\Builder<5>', $query->crossJoinSub($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<5>', $query->whereExists($userQuery));
    assertType('Illuminate\Database\Query\Builder<5>', $query->orWhereExists($userQuery));
    assertType('Illuminate\Database\Query\Builder<5>', $query->whereNotExists($userQuery));
    assertType('Illuminate\Database\Query\Builder<5>', $query->orWhereNotExists($userQuery));
    assertType('Illuminate\Database\Query\Builder<5>', $query->orderBy($userQuery));
    assertType('Illuminate\Database\Query\Builder<5>', $query->orderByDesc($userQuery));
    assertType('Illuminate\Database\Query\Builder<5>', $query->union($userQuery));
    assertType('Illuminate\Database\Query\Builder<5>', $query->unionAll($userQuery));
    assertType('int', $query->insertUsing([], $userQuery));
    assertType('int', $query->insertOrIgnoreUsing([], $userQuery));

    $query->chunk(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, array|object>', $users);
        assertType('int', $page);
    });
    $query->chunkById(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, array|object>', $users);
        assertType('int', $page);
    });
    $query->chunkMap(function ($users) {
        assertType('array|object', $users);
    });
    $query->chunkByIdDesc(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, array|object>', $users);
        assertType('int', $page);
    });
    $query->each(function ($users, $page) {
        assertType('array|object', $users);
        assertType('int', $page);
    });
    $query->eachById(function ($users, $page) {
        assertType('array|object', $users);
        assertType('int', $page);
    });
}

/**
 * @param  \Illuminate\Database\Query\Builder<\PDO::FETCH_ASSOC>  $query
 * @param  \Illuminate\Database\Eloquent\Builder<\User>  $userQuery
 */
function testWithFetchArr(Builder $query, EloquentBuilder $userQuery): void
{
    assertType('array|null', $query->first());
    assertType('array|null', $query->find(1));
    assertType('array|int', $query->findOr(1, fn () => 42));
    assertType('array|int', $query->findOr(1, callback: fn () => 42));
    assertType('Illuminate\Database\Query\Builder<2>', $query->selectSub($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<2>', $query->fromSub($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<2>', $query->from($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<2>', $query->joinSub($userQuery, 'alias', 'foo'));
    assertType('Illuminate\Database\Query\Builder<2>', $query->joinLateral($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<2>', $query->leftJoinLateral($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<2>', $query->leftJoinSub($userQuery, 'alias', 'foo'));
    assertType('Illuminate\Database\Query\Builder<2>', $query->rightJoinSub($userQuery, 'alias', 'foo'));
    assertType('Illuminate\Database\Query\Builder<2>', $query->crossJoinSub($userQuery, 'alias'));
    assertType('Illuminate\Database\Query\Builder<2>', $query->whereExists($userQuery));
    assertType('Illuminate\Database\Query\Builder<2>', $query->orWhereExists($userQuery));
    assertType('Illuminate\Database\Query\Builder<2>', $query->whereNotExists($userQuery));
    assertType('Illuminate\Database\Query\Builder<2>', $query->orWhereNotExists($userQuery));
    assertType('Illuminate\Database\Query\Builder<2>', $query->orderBy($userQuery));
    assertType('Illuminate\Database\Query\Builder<2>', $query->orderByDesc($userQuery));
    assertType('Illuminate\Database\Query\Builder<2>', $query->union($userQuery));
    assertType('Illuminate\Database\Query\Builder<2>', $query->unionAll($userQuery));
    assertType('int', $query->insertUsing([], $userQuery));
    assertType('int', $query->insertOrIgnoreUsing([], $userQuery));

    $query->chunk(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, array|object>', $users);
        assertType('int', $page);
    });
    $query->chunkById(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, array|object>', $users);
        assertType('int', $page);
    });
    $query->chunkMap(function ($users) {
        assertType('array|object', $users);
    });
    $query->chunkByIdDesc(1, function ($users, $page) {
        assertType('Illuminate\Support\Collection<int, array|object>', $users);
        assertType('int', $page);
    });
    $query->each(function ($users, $page) {
        assertType('array|object', $users);
        assertType('int', $page);
    });
    $query->eachById(function ($users, $page) {
        assertType('array|object', $users);
        assertType('int', $page);
    });
}
