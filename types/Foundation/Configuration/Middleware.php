<?php

use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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

$middleware->trustProxies(at: '*', withHeaders: Request::HEADER_X_FORWARDED_AWS_ELB);

$middleware->trustProxies(withHeaders: Request::HEADER_X_FORWARDED_AWS_ELB);
