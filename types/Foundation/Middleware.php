<?php

use Illuminate\Foundation\Configuration\Middleware;

$middleware = new Middleware();

$middleware->convertEmptyStringsToNull(except: [
    fn ($request) => $request->has('skip-all-1'),
    fn ($request) => $request->has('skip-all-2'),
]);
