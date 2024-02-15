<?php

use Illuminate\Foundation\Configuration\Middleware;

$middleware = new Middleware();

$middleware->trimStrings(except: [
    'aaa',
    fn ($request) => $request->has('skip-all'),
]);

$middleware->encryptCookies();
$middleware->encryptCookies([
    'cookie1',
    'cookie2',
]);
