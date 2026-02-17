<?php

namespace Illuminate\Tests\Cache;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Mockery as m;
use Mockery\LegacyMockInterface;
use PHPUnit\Framework\TestCase;

class CacheSpyMemoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        parent::tearDown();
    }

    public function testCacheSpyWorksWithMemoizedCache()
    {
        $cache = Cache::spy();

        Cache::memo()->remember('key', 60, fn () => 'bar');

        $cache->shouldHaveReceived('memo')->once();
    }

    public function testCacheSpyTracksRememberOnMemoizedCacheAsDescribedInIssue()
    {
        $cache = Cache::spy();

        $memoizedCache = Cache::memo();
        $value = $memoizedCache->remember('key', 60, fn () => 'bar');

        $this->assertSame('bar', $value);

        $memoizedCache->shouldHaveReceived('remember')->once()->with('key', 60, m::type(Closure::class));
    }

    public function testCacheSpyTracksRememberCallsOnMemoizedCache()
    {
        $cache = Cache::spy();

        $memoizedCache = Cache::memo();
        $memoizedCache->remember('key', 60, fn () => 'bar');

        $memoizedCache->shouldHaveReceived('remember')->once()->with('key', 60, m::type(Closure::class));
    }

    public function testCacheSpyMemoReturnsSpiedRepository()
    {
        $cache = Cache::spy();

        $memoizedCache = Cache::memo();

        $this->assertInstanceOf(LegacyMockInterface::class, $memoizedCache);

        $memoizedCache->remember('key', 60, fn () => 'bar');

        $memoizedCache->shouldHaveReceived('remember')->once();
    }
}
