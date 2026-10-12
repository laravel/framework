<?php

namespace Illuminate\Tests\Cache;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Mockery\LegacyMockInterface;
use PHPUnit\Framework\TestCase;

class CacheSpyBufferedTest extends TestCase
{
    protected function setUp(): void
    {
        $container = new Container;

        $container->instance('config', new ConfigRepository([
            'cache' => [
                'default' => 'array',
                'stores' => [
                    'array' => [
                        'driver' => 'array',
                    ],
                ],
            ],
        ]));

        $container->instance('cache', new CacheManager($container));

        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function test_cache_spy_works_with_buffered_cache()
    {
        $cache = Cache::spy();

        Cache::buffered()->remember('key', 60, fn () => 'bar');

        $cache->shouldHaveReceived('buffered')->once();
    }

    public function test_cache_spy_buffered_returns_spied_repository()
    {
        Cache::spy();

        $bufferedCache = Cache::buffered();

        $this->assertInstanceOf(LegacyMockInterface::class, $bufferedCache);

        $this->assertSame('bar', $bufferedCache->remember('key', 60, fn () => 'bar'));

        $bufferedCache->shouldHaveReceived('remember')->once()->with('key', 60, Mockery::type(Closure::class));
    }
}
