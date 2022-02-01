<?php

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as RepositoryInterface;
use function PHPStan\Testing\assertType;

/** @var RepositoryInterface $cache */
$cache = resolve(RepositoryInterface::class);

assertType('int|null', $cache->pull('cache', 10));
assertType('int', $cache->sear('cache', function (): int {
    return 12;
}));
assertType('int', $cache->rememberForever('cache', function (): int {
    return 15;
}));

/** @var Repository $cache */
$cache = resolve(Repository::class);

assertType('int|null', $cache->get('cache', 21));
assertType('int|null', $cache->pull('cache', 22));
assertType('int', $cache->sear('cache', function (): int {
    return 24;
}));
assertType('int', $cache->rememberForever('cache', function (): int {
    return 27;
}));
