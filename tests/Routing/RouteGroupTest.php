<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\RouteGroup;
use PHPUnit\Framework\TestCase;

class RouteGroupTest extends TestCase
{
    public function test_merge_combines_basic_attributes()
    {
        $old = ['middleware' => ['auth']];
        $new = ['middleware' => ['throttle']];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame(['auth', 'throttle'], $result['middleware']);
    }

    public function test_merge_formats_namespace()
    {
        $old = ['namespace' => 'App\Http\Controllers'];
        $new = ['namespace' => 'Admin'];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('App\Http\Controllers\Admin', $result['namespace']);
    }

    public function test_merge_namespace_with_leading_backslash_replaces_old()
    {
        $old = ['namespace' => 'App\Http\Controllers'];
        $new = ['namespace' => '\Admin\Controllers'];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('Admin\Controllers', $result['namespace']);
    }

    public function test_merge_preserves_old_namespace_when_new_is_not_set()
    {
        $old = ['namespace' => 'App\Http\Controllers'];
        $new = [];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('App\Http\Controllers', $result['namespace']);
    }

    public function test_merge_returns_null_namespace_when_neither_set()
    {
        $result = RouteGroup::merge([], []);

        $this->assertNull($result['namespace']);
    }

    public function test_merge_formats_prefix()
    {
        $old = ['prefix' => 'api'];
        $new = ['prefix' => 'v1'];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('api/v1', $result['prefix']);
    }

    public function test_merge_prefix_with_prepend_existing_prefix_false()
    {
        $old = ['prefix' => 'api'];
        $new = ['prefix' => 'v1'];

        $result = RouteGroup::merge($new, $old, false);

        $this->assertSame('v1/api', $result['prefix']);
    }

    public function test_merge_preserves_old_prefix_when_new_is_not_set()
    {
        $old = ['prefix' => 'api'];
        $new = [];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('api', $result['prefix']);
    }

    public function test_merge_trims_slashes_from_prefix()
    {
        $old = ['prefix' => '/api/'];
        $new = ['prefix' => '/v1/'];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('api/v1', $result['prefix']);
    }

    public function test_merge_formats_where_constraints()
    {
        $old = ['where' => ['id' => '[0-9]+']];
        $new = ['where' => ['slug' => '[a-z]+']];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame(['id' => '[0-9]+', 'slug' => '[a-z]+'], $result['where']);
    }

    public function test_merge_new_where_overrides_old_where()
    {
        $old = ['where' => ['id' => '[0-9]+']];
        $new = ['where' => ['id' => '[a-z]+']];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame(['id' => '[a-z]+'], $result['where']);
    }

    public function test_merge_formats_as_clause()
    {
        $old = ['as' => 'admin.'];
        $new = ['as' => 'users.'];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('admin.users.', $result['as']);
    }

    public function test_merge_preserves_old_as_when_new_is_not_set()
    {
        $old = ['as' => 'admin.'];
        $new = [];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('admin.', $result['as']);
    }

    public function test_merge_domain_in_new_removes_old_domain()
    {
        $old = ['domain' => 'api.example.com'];
        $new = ['domain' => 'admin.example.com'];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('admin.example.com', $result['domain']);
    }

    public function test_merge_controller_in_new_removes_old_controller()
    {
        $old = ['controller' => 'App\Http\Controllers\UserController'];
        $new = ['controller' => 'App\Http\Controllers\AdminController'];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('App\Http\Controllers\AdminController', $result['controller']);
    }

    public function test_merge_complex_scenario()
    {
        $old = [
            'namespace' => 'App\Http\Controllers',
            'prefix' => 'api',
            'as' => 'api.',
            'middleware' => ['auth'],
            'where' => ['id' => '[0-9]+'],
        ];

        $new = [
            'namespace' => 'Admin',
            'prefix' => 'v1',
            'as' => 'admin.',
            'middleware' => ['throttle'],
            'where' => ['slug' => '[a-z]+'],
        ];

        $result = RouteGroup::merge($new, $old);

        $this->assertSame('App\Http\Controllers\Admin', $result['namespace']);
        $this->assertSame('api/v1', $result['prefix']);
        $this->assertSame('api.admin.', $result['as']);
        $this->assertSame(['auth', 'throttle'], $result['middleware']);
        $this->assertSame(['id' => '[0-9]+', 'slug' => '[a-z]+'], $result['where']);
    }
}
