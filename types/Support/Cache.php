<?php

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as RepositoryInterface;
use function PHPStan\Testing\assertType;

/** @var RepositoryInterface $cache */
$cache = resolve(RepositoryInterface::class);

assertType('int', $cache->pull('cache', 13));
assertType('int', $cache->pull('cache', function (): int {
    return 12;
}));
assertType('int', $cache->sear('cache', function (): int {
    return 15;
}));
assertType('int', $cache->rememberForever('cache', function (): int {
    return 18;
}));

/** @var Repository $cache */
$cache = resolve(Repository::class);

assertType('int', $cache->get('cache', 27));
assertType('int', $cache->get('cache', function (): int {
    return 26;
}));
assertType('int', $cache->pull('cache', 28));
assertType('int', $cache->pull('cache', function (): int {
    return 30;
}));
assertType('int', $cache->sear('cache', function (): int {
    return 33;
}));
assertType('int', $cache->rememberForever('cache', function (): int {
    return 36;
}));
