<?php

namespace Illuminate\Tests\Integration\Foundation\Support\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\Assert;
use Orchestra\Testbench\TestCase;

class RouteServiceProviderTest extends TestCase
{
    /**
     * Resolve application implementation.
     *
     * @return \Illuminate\Foundation\Application
     */
    protected function resolveApplication()
    {
        return require __DIR__.'/bootstrap.php';
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            AppRouteServiceProvider::class,
        ];
    }

    public function test_it_can_register_multiple_route_service_providers()
    {
        Assert::assertArraySubset([
            RouteServiceProvider::class => true,
            AppRouteServiceProvider::class => true,
        ], $this->app->getLoadedProviders());
    }

    public function test_it_can_uses_routes_registered_using_bootstrap_file()
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Login');
    }

    public function test_it_can_uses_routes_registered_using_configuration_file()
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Hello');
    }
}

class AppRouteServiceProvider extends RouteServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->routes(function () {
            Route::get('dashboard', fn () => 'Hello')->name('dashboard');
        });
    }
}
