<?php

use Illuminate\Foundation\Configuration\Middleware;

$middleware = new Middleware();

$middleware->trimStrings(except: [
    'aaa',
    fn ($request) => $request->has('skip-all'),
]);

$middleware->trustHosts();
$middleware->trustHosts(at: ['laravel.test']);
$middleware->trustHosts(at: ['laravel.test'], subdomains: false);

$middleware->encryptCookies();
$middleware->encryptCookies([
    'cookie1',
    'cookie2',
]);
