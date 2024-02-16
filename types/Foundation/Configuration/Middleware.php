<?php

use Illuminate\Foundation\Configuration\Middleware;

$middleware = new Middleware();

$middleware->convertEmptyStringsToNull(except: [
    fn ($request) => $request->has('skip-all-1'),
    fn ($request) => $request->has('skip-all-2'),
]);

$middleware->trimStrings(except: [
    'aaa',
    fn ($request) => $request->has('skip-all'),
]);

$middleware->encryptCookies();
$middleware->encryptCookies([
    'cookie1',
    'cookie2',
]);
