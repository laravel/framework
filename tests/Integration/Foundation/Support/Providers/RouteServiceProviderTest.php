<?php

namespace Illuminate\Tests\Integration\Foundation\Support\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\Assert;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

#[WithConfig('filesystems.disks.local.serve', false)]
class RouteServiceProviderTest extends TestCase
{
    /**
     * Resolve application implementation.
     *
     * @return \Illuminate\Foundation\Application
     */
    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withProviders([
                AppRouteServiceProvider::class,
            ])
            ->withRouting(
                using: function () {
                    Route::get('login', fn () => 'Login')->name('login');
                }
            )
            ->withMiddleware(function (Middleware $middleware) {
                //
            })
            ->withExceptions(function (Exceptions $exceptions) {
                //
            })->create();
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

    #[WithConfig('app.url', 'https://app.example')]
    public function test_default_proxy_middleware_uses_configured_url_when_hosts_are_not_restricted()
    {
        $this->app['router']->get('generated-url', fn () => url('profile'));

        $this->get('http://attacker.example/generated-url')
            ->assertOk()
            ->assertSeeText('https://app.example/profile');
    }

    #[WithConfig('app.url', 'https://app.example')]
    public function test_application_origin_remains_available_to_streamed_responses()
    {
        $this->app['router']->get('streamed-url', fn () => response()->stream(fn () => print url('profile')));

        $response = $this->get('http://attacker.example/streamed-url');

        $response->assertOk();
        $this->assertSame('https://app.example/profile', $response->streamedContent());
    }

    #[WithConfig('app.url', 'https://app.example')]
    public function test_application_origin_remains_available_to_request_handled_listeners()
    {
        $generatedUrl = null;
        $this->app['events']->listen(RequestHandled::class, function () use (&$generatedUrl) {
            $generatedUrl = url('profile');
        });
        $this->app['router']->get('handled-url', fn () => '');

        $this->get('http://attacker.example/handled-url')->assertOk();

        $this->assertSame('https://app.example/profile', $generatedUrl);
    }

    #[WithConfig('app.url', 'https://app.example')]
    #[TestWith([[], 'https://app.example/profile'])]
    #[TestWith([['.*'], 'https://app.example/profile'])]
    #[TestWith([['^forwarded\.example$'], 'http://forwarded.example/profile'])]
    public function test_proxy_middleware_uses_application_url_based_on_trusted_hosts(array $trustedHosts, string $expected)
    {
        Request::setTrustedHosts($trustedHosts);
        TrustProxies::at('*');

        try {
            $this->app['router']->get('generated-url', fn () => [
                'host' => request()->getHost(),
                'url' => url('profile'),
            ]);

            $this->get('http://proxy.example/generated-url', [
                'X-Forwarded-Host' => 'forwarded.example',
                'X-Forwarded-Proto' => 'http',
            ])->assertOk()
                ->assertJsonPath('host', 'forwarded.example')
                ->assertJsonPath('url', $expected);
        } finally {
            Request::setTrustedHosts([]);
            TrustProxies::flushState();
        }
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
