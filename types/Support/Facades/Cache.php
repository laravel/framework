<?php

use Illuminate\Support\Facades\Cache;

use function PHPStan\Testing\assertType;

assertType('mixed', Cache::get('key'));
assertType('mixed', Cache::get('cache', 27));
assertType('mixed', Cache::get('cache', function (): int {
    return 26;
}));

assertType('mixed', Cache::pull('key'));
assertType('mixed', Cache::pull('cache', 28));
assertType('mixed', Cache::pull('cache', function (): int {
    return 30;
}));
assertType('mixed', Cache::sear('cache', function (): int {
    return 33;
}));
assertType('mixed', Cache::remember('cache', now(), function (): int {
    return 36;
}));
assertType('mixed', Cache::rememberForever('cache', function (): int {
    return 36;
}));
