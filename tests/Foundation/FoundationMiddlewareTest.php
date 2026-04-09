<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Attributes\Controllers\AsMiddleware;
use PHPUnit\Framework\TestCase;

class FoundationMiddlewareTest extends TestCase
{
    public function test_middleware_can_be_discovered_via_attribute()
    {
        $middleware = new Middleware;

        $middleware->alias([
            AttributeMiddlewareStub::class,
        ]);

        $aliases = $middleware->getMiddlewareAliases();

        $this->assertArrayHasKey('auth', $aliases);
        $this->assertEquals(AttributeMiddlewareStub::class, $aliases['auth']);
    }

    public function test_middleware_can_be_manually_aliased_the_old_way()
    {
        $middleware = new Middleware;

        $middleware->alias([
            'old.auth' => PlainMiddlewareStub::class,
        ]);

        $aliases = $middleware->getMiddlewareAliases();

        $this->assertArrayHasKey('old.auth', $aliases);
        $this->assertEquals(PlainMiddlewareStub::class, $aliases['old.auth']);
    }

    public function test_middleware_can_handle_mixed_aliasing()
    {
        $middleware = new Middleware;

        $middleware->alias([
            'manual.alias' => PlainMiddlewareStub::class,
            AttributeMiddlewareStub::class,
        ]);

        $aliases = $middleware->getMiddlewareAliases();

        $this->assertArrayHasKey('auth', $aliases);

        $this->assertArrayHasKey('manual.alias', $aliases);
    }

    public function test_manual_alias_takes_precedence_over_attribute_alias()
    {
        $middleware = new Middleware;

        $middleware->alias([
            'manual.auth' => AttributeMiddlewareStub::class,
        ]);

        $aliases = $middleware->getMiddlewareAliases();

        $this->assertArrayHasKey('auth', $aliases);

        $this->assertArrayHasKey('manual.auth', $aliases);
        $this->assertEquals(AttributeMiddlewareStub::class, $aliases['manual.auth']);

        $middleware->alias([
            'auth' => PlainMiddlewareStub::class,
        ]);

        $mergedAliases = $middleware->getMiddlewareAliases();

        $this->assertEquals(PlainMiddlewareStub::class, $mergedAliases['auth']);
    }
}

#[AsMiddleware('auth')]
class AttributeMiddlewareStub
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}

class PlainMiddlewareStub
{
    public function handle($request, $next)
    {
        return $next($request);
    }
}
