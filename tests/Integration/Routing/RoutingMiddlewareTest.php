<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Tests\Integration\Routing\Fixtures\ControllerWithStaticallyDefinedMiddleware;
use Orchestra\Testbench\TestCase;

final class RoutingMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['controller_with_statically_defined_middleware_was_constructed']);

        parent::tearDown();
    }

    public function testControllerIsNotInstantiatedWhenStaticallyDefiningMiddlewareOnIt()
    {
        $this->withoutExceptionHandling();
        $this->actingAs(new GenericUser([]));

        $_SERVER['controller_with_statically_defined_middleware_was_constructed'] = false;

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('test-route', ControllerWithStaticallyDefinedMiddleware::class);

        $response = $this->get('test-route');

        $response->assertRedirect('https://www.foo.com');

        $this->assertFalse($_SERVER['controller_with_statically_defined_middleware_was_constructed']);
    }
}
