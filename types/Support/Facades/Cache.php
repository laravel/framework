<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

use function PHPStan\Testing\assertType;

assertType('mixed', Cache::get('key'));
assertType('mixed', Cache::get('cache', 27));
assertType('mixed', Cache::get('cache', function (): int {
    return 26;
}));

assertType('array<mixed>', Cache::store('redis')->many(['key']));
assertType('bool', Cache::store('redis')->putMany(['key' => 'value'], 60));
assertType('array<mixed>', Cache::driver('file')->many(['key']));
assertType('bool', Cache::driver('file')->putMany(['key' => 'value'], 60));
assertType('array<mixed>', Cache::memo('array')->many(['key']));
assertType('bool', Cache::memo('array')->putMany(['key' => 'value'], 60));

assertType('mixed', Cache::pull('key'));
assertType('mixed', Cache::pull('cache', 28));
assertType('mixed', Cache::pull('cache', function (): int {
    return 30;
}));
assertType('mixed', Cache::sear('cache', function (): int {
    return 33;
}));
assertType('mixed', Cache::remember('cache', Carbon::now(), function (): int {
    return 36;
}));
assertType('array', Cache::rememberWithWarmth('cache', Carbon::now(), function (): int {
    return 36;
}));
assertType('mixed', Cache::rememberForever('cache', function (): int {
    return 36;
}));
