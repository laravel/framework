<?php

namespace Illuminate\Tests\Testing\Concerns;

use Generator;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\ParallelTesting as ParallelTestingFacade;
use Illuminate\Testing\Concerns\TestCaches;
use Illuminate\Testing\ParallelTesting;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class TestCachesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($container = new Container);

        Facade::setFacadeApplication($container);

        $container->singleton('config', fn () => new Config([
            'cache' => [
                'prefix' => 'myapp_cache_',
            ],
        ]));

        $container->singleton(ParallelTesting::class, fn ($app) => new ParallelTesting($app));

        $_SERVER['LARAVEL_PARALLEL_TESTING'] = 1;
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        ParallelTestingFacade::clearResolvedInstance();
        Facade::setFacadeApplication(null);

        unset($_SERVER['LARAVEL_PARALLEL_TESTING']);

        // Reset static property between tests
        $instance = new class
        {
            use TestCaches;

            public $app;

            public function __construct()
            {
                $this->app = Container::getInstance() ?? new Container;
            }
        };

        (new ReflectionProperty($instance::class, 'originalCachePrefix'))->setValue(null, null);

        parent::tearDown();
    }

    #[DataProvider('cachePrefixes')]
    public function testCachePrefixAppendsToken(string $prefix, string $token, string $expected)
    {
        Container::getInstance()['config']->set('cache.prefix', $prefix);
        Container::getInstance()->make(ParallelTesting::class)->resolveTokenUsing(fn () => $token);

        $this->assertSame($expected, $this->getParallelSafeCachePrefix());
    }

    public static function cachePrefixes(): Generator
    {
        yield 'with prefix' => ['myapp_cache_', '5', 'myapp_cache_test_5_'];
        yield 'empty prefix' => ['', '3', 'test_3_'];
    }

    public function testCachePrefixPreservesOriginalPrefix()
    {
        Container::getInstance()->make(ParallelTesting::class)->resolveTokenUsing(fn () => '1');

        $this->getParallelSafeCachePrefix();

        Container::getInstance()->make(ParallelTesting::class)->resolveTokenUsing(fn () => '2');

        $this->assertSame('myapp_cache_test_2_', $this->getParallelSafeCachePrefix());
    }

    public function testSwitchToCachePrefixUpdatesConfig()
    {
        $this->switchToCachePrefix('new_prefix_');

        $this->assertSame('new_prefix_', Container::getInstance()['config']->get('cache.prefix'));
    }

    public function testBootTestCacheRegistersSetUpTestCaseCallback()
    {
        Container::getInstance()->make(ParallelTesting::class)->resolveTokenUsing(fn () => '7');

        $instance = $this->makeTestCachesInstance();

        (new ReflectionProperty($instance::class, 'originalCachePrefix'))->setValue(null, null);

        $method = new ReflectionMethod($instance, 'bootTestCache');
        $method->invoke($instance);

        $parallelTesting = Container::getInstance()->make(ParallelTesting::class);
        $setUpCallbacks = (new ReflectionProperty($parallelTesting, 'setUpTestCaseCallbacks'))->getValue($parallelTesting);

        $this->assertCount(1, $setUpCallbacks);
    }

    public function testBootTestCacheSkipsIsolationIfOptedOut()
    {
        Container::getInstance()->make(ParallelTesting::class)->resolveTokenUsing(fn () => '7');

        $instance = $this->makeTestCachesInstance();

        (new ReflectionProperty($instance::class, 'originalCachePrefix'))->setValue(null, null);
        (new ReflectionMethod($instance, 'bootTestCache'))->invoke($instance);

        $_SERVER['LARAVEL_PARALLEL_TESTING_WITHOUT_CACHE'] = 1;

        Container::getInstance()->make(ParallelTesting::class)->callSetUpTestCaseCallbacks(new class { });

        $this->assertSame('myapp_cache_', Container::getInstance()['config']->get('cache.prefix'));

        unset($_SERVER['LARAVEL_PARALLEL_TESTING_WITHOUT_CACHE']);
    }

    protected function getParallelSafeCachePrefix()
    {
        $instance = $this->makeTestCachesInstance();

        (new ReflectionProperty($instance::class, 'originalCachePrefix'))->setValue(null, null);

        $method = new ReflectionMethod($instance, 'parallelSafeCachePrefix');

        return $method->invoke($instance);
    }

    protected function switchToCachePrefix($prefix)
    {
        $instance = $this->makeTestCachesInstance();

        $method = new ReflectionMethod($instance, 'switchToCachePrefix');
        $method->invoke($instance, $prefix);
    }

    protected function makeTestCachesInstance()
    {
        return new class
        {
            use TestCaches;

            public $app;

            public function __construct()
            {
                $this->app = Container::getInstance();
            }
        };
    }
}
