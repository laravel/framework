<?php

use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$middleware = new Middleware();

$middleware->convertEmptyStringsToNull(except: [
    fn (Request $request): bool => $request->has('skip-all-1'),
    fn (Request $request): bool => $request->has('skip-all-2'),
]);

$middleware->trimStrings(except: [
    'aaa',
    fn (Request $request): bool => $request->has('skip-all'),
]);

$middleware->trustProxies(at: '*');
$middleware->trustProxies(at: [
    '192.168.1.1',
    '192.168.1.2',
]);

$middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_AWS_ELB);

$middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_AWS_ELB);

$middleware->trustHosts();
$middleware->trustHosts(at: ['laravel.test']);
$middleware->trustHosts(at: ['laravel.test'], subdomains: false);

$middleware->encryptCookies();
$middleware->encryptCookies([
    'cookie1',
    'cookie2',
]);
