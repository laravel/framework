<?php

namespace Illuminate\Tests\Queue;

use BadMethodCallException;
use Illuminate\Queue\Middleware\HasMiddlewareHooks;
use Illuminate\Queue\Middleware\WrappedMiddleware;
use PHPUnit\Framework\TestCase;

class HasMiddlewareHooksTest extends TestCase
{
    public function testCreatesAndReturnsWrappedMiddleware()
    {
        $getMiddleware = function () {
            return new class {
                use HasMiddlewareHooks;

                public function handle($job, $next)
                {
                    $next($job);
                }
            };
        };

        $this->assertNotInstanceOf(WrappedMiddleware::class, $getMiddleware());
        $this->assertInstanceOf(WrappedMiddleware::class, $getMiddleware()->before(fn($job) => true));
        $this->assertInstanceOf(WrappedMiddleware::class, $getMiddleware()->after(fn($job) => true));
        $this->assertInstanceOf(WrappedMiddleware::class, $getMiddleware()->onFail(fn($job) => true));
        $this->assertInstanceOf(WrappedMiddleware::class, $getMiddleware()->addHook('before', fn($job) => true));
    }

    public function testThrowsExceptionWhenHookIsNotSupported()
    {
        $this->expectException(BadMethodCallException::class);

        $middleware = new class {
            use HasMiddlewareHooks;

            public function handle($job, $next)
            {
                $next($job);
            }
        };

        $middleware->addHook('unsupported', fn($job) => true);
    }

    public function testChainedMethodsReturnSameInstance()
    {
        $middleware = new class {
            use HasMiddlewareHooks;

            public function handle($job, $next)
            {
                $next($job);
            }
        };

        $beforeInstance = $middleware->before(fn($job) => true);
        $afterInstance = $middleware->after(fn($job) => true);
        $onFailInstance = $middleware->onFail(fn($job) => true);

        $this->assertSame($beforeInstance, $afterInstance);
        $this->assertSame($beforeInstance, $onFailInstance);
    }
}
