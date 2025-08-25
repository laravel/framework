<?php

use Illuminate\Config\Repository;

use function PHPStan\Testing\assertType;

assertType('Illuminate\Foundation\Application', app());
assertType('mixed', app('foo'));
assertType('Illuminate\Config\Repository', app(Repository::class));

assertType('Illuminate\Contracts\Auth\Factory', auth());
assertType('Illuminate\Contracts\Auth\StatefulGuard', auth('foo'));

assertType('Illuminate\Cache\CacheManager', cache());
assertType('bool', cache(['foo' => 'bar'], 42));
assertType('mixed', cache('foo', 42));

assertType('Illuminate\Config\Repository', config());
assertType('null', config(['foo' => 'bar']));
assertType('mixed', config('foo'));

assertType('Illuminate\Log\Context\Repository', context());
assertType('Illuminate\Log\Context\Repository', context(['foo' => 'bar']));
assertType('mixed', context('foo'));

assertType('Illuminate\Cookie\CookieJar', cookie());
assertType('Symfony\Component\HttpFoundation\Cookie', cookie('foo'));

assertType('Illuminate\Foundation\Bus\PendingDispatch', dispatch('foo'));
assertType('Illuminate\Foundation\Bus\PendingClosureDispatch', dispatch(fn () => 1));

assertType('Illuminate\Log\LogManager', logger());
assertType('null', logger('foo'));

assertType('Illuminate\Log\LogManager', logs());
assertType('Psr\Log\LoggerInterface', logs('foo'));

assertType('123|null', rescue(fn () => 123));
assertType('123|345', rescue(fn () => 123, 345));
assertType('123|345', rescue(fn () => 123, fn () => 345));

assertType('Illuminate\Routing\Redirector', redirect());
assertType('Illuminate\Http\RedirectResponse', redirect('foo'));

assertType('mixed', resolve('foo'));
assertType('Illuminate\Config\Repository', resolve(Repository::class));

assertType('Illuminate\Http\Request', request());
assertType('mixed', request('foo'));
assertType('array<string, mixed>', request(['foo', 'bar']));

assertType('Illuminate\Contracts\Routing\ResponseFactory', response());
assertType('Illuminate\Http\Response', response('foo'));

assertType('Illuminate\Session\SessionManager', session());
assertType('mixed', session('foo'));
assertType('null', session(['foo' => 'bar']));

assertType('Illuminate\Contracts\Translation\Translator', trans());
assertType('array|string', trans('foo'));

assertType('Illuminate\Contracts\Validation\Factory', validator());
assertType('Illuminate\Contracts\Validation\Validator', validator([]));

assertType('Illuminate\Contracts\View\Factory', view());
assertType('Illuminate\Contracts\View\View', view('foo'));

assertType('Illuminate\Contracts\Routing\UrlGenerator', url());
assertType('string', url('foo'));
