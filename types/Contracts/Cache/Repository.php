<?php

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;

use function PHPStan\Testing\assertType;

/** @var Repository $cache */
$cache = resolve(Repository::class);

assertType('mixed', $cache->get('key'));
assertType('mixed', $cache->get('cache', 27));
assertType('mixed', $cache->get('cache', function (): int {
    return 26;
}));

assertType('array<mixed>', $cache->many(['key']));
assertType('bool', $cache->putMany(['key' => 'value']));
assertType('bool', $cache->putMany(['key' => 'value'], 60));
assertType('bool', $cache->putMany(['key' => 'value'], new DateInterval('PT1M')));
assertType('bool', $cache->putMany(['key' => 'value'], Carbon::now()->addMinute()));

assertType('mixed', $cache->pull('key'));
assertType('28', $cache->pull('cache', 28));
assertType('30', $cache->pull('cache', function (): int {
    return 30;
}));
assertType('33', $cache->sear('cache', function (): int {
    return 33;
}));
assertType('36', $cache->remember('cache', Carbon::now(), function (): int {
    return 36;
}));
assertType('36', $cache->rememberForever('cache', function (): int {
    return 36;
}));
