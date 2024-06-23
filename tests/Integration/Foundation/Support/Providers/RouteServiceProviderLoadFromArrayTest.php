<?php

namespace Illuminate\Tests\Integration\Foundation\Support\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class RouteServiceProviderLoadFromArrayTest extends TestCase
{
    /**
     * Resolve application implementation.
     *
     * @return \Illuminate\Foundation\Application
     */
    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withRouting(
                web: [
                    __DIR__.'/fixtures/web.php',
                    __DIR__.'/fixtures/admin.php',
                ],
                api: [
                    __DIR__.'/fixtures/api.php',
                    __DIR__.'/fixtures/apiAdmin.php',
                ],
            )->create();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
    }

    public function test_it_can_uses_routes_registered_using_route_files_array()
    {
        $this->get(route('user', [1]))->assertOk();
        $this->get(route('admin.user', [1]))->assertOk();
    }

    public function test_it_can_uses_api_routes_registered_using_route_files_array()
    {
        $this->get(route('api.user', [1]))->assertOk();
        $this->get(route('api.admin.user', [1]))->assertOk();
    }
}
