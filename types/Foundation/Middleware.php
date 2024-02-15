<?php

use Illuminate\Foundation\Configuration\Middleware;

use function PHPStan\Testing\assertType;

$middleware = new Middleware();

$middleware->convertEmptyStringsToNull(except: [
    fn ($request) => $request->has('skip-all-1'),
    fn ($request) => $request->has('skip-all-2'),
]);

