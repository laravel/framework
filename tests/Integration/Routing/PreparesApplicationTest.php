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

    public function testWithDifferentServiceDependencies()
    {
        RouteFacade::domain('foo.localhost')->get('/bar', PreparesApplicationController::class)->middleware(PreparesApplicationMiddleware::class);

        $this->withoutExceptionHandling()->get('http://foo.localhost/bar')->assertSee('tenant_foo');
    }
}

class PreparesApplicationController
{
    public function __construct(
        public PreparesApplicationService $service,
    ) {}

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
    ) {}

    public function prepareApplication(Route $route)
    {
        if ($domain = $route->getDomain()) {
            $this->app->instance('prepares_application.context', 'tenant_' . $domain);
        }
    }

    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
