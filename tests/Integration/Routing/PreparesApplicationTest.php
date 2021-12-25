<?php

namespace Illuminate\Tests\Integration\Routing;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\Contracts\PreparesApplication;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Orchestra\Testbench\TestCase;

class PreparesApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance('prepares_application.context', 'central');
    }

    public function testWithNoSideEffects()
    {
        RouteFacade::get('/foo', PreparesApplicationController::class)->middleware(PreparesApplicationMiddleware::class);

        $this->get('/foo')->assertSee('central');
    }

    public function testRequestStateIsAccessible()
    {
        RouteFacade::get('/foo', PreparesApplicationController::class)->middleware(PreparesApplicationMiddleware::class);

        $this->get('/foo?context=bar')->assertSee('bar');
    }

    public function testWithDifferentServiceDependencies()
    {
        RouteFacade::domain('foo.localhost')->get('/bar', PreparesApplicationController::class)->middleware(PreparesApplicationMiddleware::class);

        $this->withoutExceptionHandling()->get('http://foo.localhost/bar')->assertSee('tenant_foo');
    }

    public function testRouteRegistrationHasNoSideEffects()
    {
        // Ensure that the logic is executed when the route is being *used* by a request, rather than when it's being *registered*

        RouteFacade::domain('foo.localhost')->get('/bar', PreparesApplicationController::class)->middleware(PreparesApplicationMiddleware::class);
        RouteFacade::get('/baz', PreparesApplicationController::class);

        $this->get('/baz')->assertSee('central');
    }

    public function testRouteGroupsAreSupported()
    {
        RouteFacade::middleware(PreparesApplicationMiddleware::class)->group(function () {
            RouteFacade::get('/foo', PreparesApplicationController::class);
        });

        $this->get('/foo?context=bar')->assertSee('bar');
    }
}

class PreparesApplicationController
{
    public function __construct(
        public PreparesApplicationService $service,
    ) {
    }

    public function __invoke()
    {
        return $this->service->context;
    }
}

class PreparesApplicationService
{
    public string $context;

    public function __construct(Container $app)
    {
        $this->context = $app->make('prepares_application.context');
    }
}

class PreparesApplicationMiddleware implements PreparesApplication
{
    public function __construct(
        public Container $app
    ) {
    }

    public function prepareApplication(Route $route)
    {
        if ($context = request()->query('context')) {
            $this->app->instance('prepares_application.context', $context);

            return;
        }

        if ($domain = $route->getDomain()) {
            $this->app->instance('prepares_application.context', 'tenant_'.$domain);
        }
    }

    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
