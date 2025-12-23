<?php

namespace Illuminate\Tests\Cache;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\LegacyMockInterface;
use PHPUnit\Framework\TestCase;

class CacheSpyMemoTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function test_cache_spy_works_with_memoized_cache()
    {
        $cache = Cache::spy();

        Cache::memo()->remember('key', 60, fn () => 'bar');

        $cache->shouldHaveReceived('memo')->once();
    }

    public function test_cache_spy_tracks_remember_on_memoized_cache_as_described_in_issue()
    {
        $cache = Cache::spy();

        $memoizedCache = Cache::memo();
        $value = $memoizedCache->remember('key', 60, fn () => 'bar');

        $this->assertSame('bar', $value);

        $memoizedCache->shouldHaveReceived('remember')->once()->with('key', 60, Mockery::type(Closure::class));
    }

    public function test_cache_spy_tracks_remember_calls_on_memoized_cache()
    {
        $cache = Cache::spy();

        $memoizedCache = Cache::memo();
        $memoizedCache->remember('key', 60, fn () => 'bar');

        $memoizedCache->shouldHaveReceived('remember')->once()->with('key', 60, Mockery::type(Closure::class));
    }

    public function test_cache_spy_memo_returns_spied_repository()
    {
        $cache = Cache::spy();

        $memoizedCache = Cache::memo();

        $this->assertInstanceOf(LegacyMockInterface::class, $memoizedCache);

        $memoizedCache->remember('key', 60, fn () => 'bar');

        $memoizedCache->shouldHaveReceived('remember')->once();
    }
}
