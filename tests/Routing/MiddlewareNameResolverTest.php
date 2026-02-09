<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\MiddlewareNameResolver;
use PHPUnit\Framework\TestCase;

class MiddlewareNameResolverTest extends TestCase
{
    public function test_resolve_returns_closure_when_given_closure()
    {
        $closure = function () {
            return 'middleware';
        };

        $result = MiddlewareNameResolver::resolve($closure, [], []);

        $this->assertSame($closure, $result);
    }

    public function test_resolve_returns_closure_from_map()
    {
        $closure = function () {
            return 'middleware';
        };

        $map = ['auth' => $closure];

        $result = MiddlewareNameResolver::resolve('auth', $map, []);

        $this->assertSame($closure, $result);
    }

    public function test_resolve_returns_class_name_from_map()
    {
        $map = ['auth' => 'App\Http\Middleware\Authenticate'];

        $result = MiddlewareNameResolver::resolve('auth', $map, []);

        $this->assertSame('App\Http\Middleware\Authenticate', $result);
    }

    public function test_resolve_returns_class_name_with_parameters()
    {
        $map = ['auth' => 'App\Http\Middleware\Authenticate'];

        $result = MiddlewareNameResolver::resolve('auth:api,guard', $map, []);

        $this->assertSame('App\Http\Middleware\Authenticate:api,guard', $result);
    }

    public function test_resolve_returns_original_name_when_not_in_map()
    {
        $result = MiddlewareNameResolver::resolve('App\Http\Middleware\Custom', [], []);

        $this->assertSame('App\Http\Middleware\Custom', $result);
    }

    public function test_resolve_returns_original_name_with_parameters_when_not_in_map()
    {
        $result = MiddlewareNameResolver::resolve('App\Http\Middleware\Custom:param1,param2', [], []);

        $this->assertSame('App\Http\Middleware\Custom:param1,param2', $result);
    }

    public function test_resolve_returns_middleware_group()
    {
        $middlewareGroups = [
            'web' => [
                'App\Http\Middleware\EncryptCookies',
                'App\Http\Middleware\StartSession',
            ],
        ];

        $result = MiddlewareNameResolver::resolve('web', [], $middlewareGroups);

        $this->assertSame([
            'App\Http\Middleware\EncryptCookies',
            'App\Http\Middleware\StartSession',
        ], $result);
    }

    public function test_resolve_middleware_group_with_mapped_middleware()
    {
        $map = [
            'encrypt' => 'App\Http\Middleware\EncryptCookies',
            'session' => 'App\Http\Middleware\StartSession',
        ];

        $middlewareGroups = [
            'web' => ['encrypt', 'session'],
        ];

        $result = MiddlewareNameResolver::resolve('web', $map, $middlewareGroups);

        $this->assertSame([
            'App\Http\Middleware\EncryptCookies',
            'App\Http\Middleware\StartSession',
        ], $result);
    }

    public function test_resolve_middleware_group_with_parameters()
    {
        $map = [
            'throttle' => 'App\Http\Middleware\ThrottleRequests',
        ];

        $middlewareGroups = [
            'api' => ['throttle:60,1'],
        ];

        $result = MiddlewareNameResolver::resolve('api', $map, $middlewareGroups);

        $this->assertSame([
            'App\Http\Middleware\ThrottleRequests:60,1',
        ], $result);
    }

    public function test_resolve_nested_middleware_groups()
    {
        $middlewareGroups = [
            'web' => [
                'App\Http\Middleware\EncryptCookies',
                'App\Http\Middleware\StartSession',
            ],
            'admin' => [
                'web',
                'App\Http\Middleware\AdminAuth',
            ],
        ];

        $result = MiddlewareNameResolver::resolve('admin', [], $middlewareGroups);

        $this->assertSame([
            'App\Http\Middleware\EncryptCookies',
            'App\Http\Middleware\StartSession',
            'App\Http\Middleware\AdminAuth',
        ], $result);
    }

    public function test_resolve_deeply_nested_middleware_groups()
    {
        $middlewareGroups = [
            'base' => [
                'App\Http\Middleware\Base',
            ],
            'web' => [
                'base',
                'App\Http\Middleware\Web',
            ],
            'admin' => [
                'web',
                'App\Http\Middleware\Admin',
            ],
        ];

        $result = MiddlewareNameResolver::resolve('admin', [], $middlewareGroups);

        $this->assertSame([
            'App\Http\Middleware\Base',
            'App\Http\Middleware\Web',
            'App\Http\Middleware\Admin',
        ], $result);
    }

    public function test_resolve_middleware_group_with_mixed_content()
    {
        $map = [
            'auth' => 'App\Http\Middleware\Authenticate',
        ];

        $middlewareGroups = [
            'web' => [
                'App\Http\Middleware\EncryptCookies',
                'auth:web',
                'App\Http\Middleware\VerifyCsrfToken',
            ],
        ];

        $result = MiddlewareNameResolver::resolve('web', $map, $middlewareGroups);

        $this->assertSame([
            'App\Http\Middleware\EncryptCookies',
            'App\Http\Middleware\Authenticate:web',
            'App\Http\Middleware\VerifyCsrfToken',
        ], $result);
    }
}
