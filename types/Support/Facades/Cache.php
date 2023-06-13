<?php

use Illuminate\Support\Facades\Cache;

use function PHPStan\Testing\assertType;

assertType('mixed', Cache::get('key'));
assertType('int', Cache::get('cache', 27));
assertType('int', Cache::get('cache', function (): int {
    return 26;
}));

assertType('mixed', Cache::pull('key'));
assertType('int', Cache::pull('cache', 28));
assertType('int', Cache::pull('cache', function (): int {
    return 30;
}));
assertType('int', Cache::sear('cache', function (): int {
    return 33;
}));
assertType('int', Cache::remember('cache', now(), function (): int {
    return 36;
}));
assertType('int', Cache::rememberForever('cache', function (): int {
    return 36;
}));
