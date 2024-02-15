<?php

use Illuminate\Foundation\Configuration\Middleware;

use function PHPStan\Testing\assertType;

$middleware = new Middleware();

$middleware->trimStrings(except: [
    'aaa',
    fn ($request) => $request->has('skip-all'),
]);

