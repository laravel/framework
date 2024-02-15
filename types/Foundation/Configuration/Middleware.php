<?php

use Illuminate\Foundation\Configuration\Middleware;

$middleware = new Middleware();

$middleware->trimStrings(except: [
    'aaa',
    fn ($request) => $request->has('skip-all'),
]);

$middleware->trustProxies(at: '*');
$middleware->trustProxies(at: [
    '192.168.1.1',
    '192.168.1.2',
]);
