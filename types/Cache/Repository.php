<?php

use Illuminate\Cache\Repository;

use function PHPStan\Testing\assertType;

/** @var Repository $cache */
$cache = resolve(Repository::class);

assertType('mixed', $cache->get('key'));
assertType('int', $cache->get('cache', 27));
assertType('int', $cache->get('cache', function (): int {
    return 26;
}));

assertType('mixed', $cache->pull('key'));
assertType('int', $cache->pull('cache', 28));
assertType('int', $cache->pull('cache', function (): int {
    return 30;
}));
assertType('int', $cache->sear('cache', function (): int {
    return 33;
}));
assertType('int', $cache->remember('cache', now(), function (): int {
    return 36;
}));
assertType('int', $cache->rememberForever('cache', function (): int {
    return 36;
}));
