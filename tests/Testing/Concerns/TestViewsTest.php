<?php

namespace Illuminate\Tests\Testing\Concerns;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\ParallelTesting as ParallelTestingFacade;
use Illuminate\Testing\Concerns\TestViews;
use Illuminate\Testing\ParallelTesting;
use Illuminate\View\Compilers\BladeCompiler;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class TestViewsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($container = new Container);

        Facade::setFacadeApplication($container);

        $container->singleton('config', fn () => new Config([
            'view' => [
                'compiled' => '/path/to/compiled/views',
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

        m::close();

        parent::tearDown();
    }

    public function testCompiledViewPathAppendsToken()
    {
        Container::getInstance()->make(ParallelTesting::class)->resolveTokenUsing(fn () => '5');

        $this->assertSame('/path/to/compiled/views/test_5', $this->testCompiledViewPath());
    }

    public function testCompiledViewPathTrimsTrailingSlash()
    {
        Container::getInstance()->make(ParallelTesting::class)->resolveTokenUsing(fn () => '3');

        Container::getInstance()['config']->set('view.compiled', '/path/to/compiled/views/');

        $this->assertSame('/path/to/compiled/views/test_3', $this->testCompiledViewPath());
    }

    public function testCompiledViewPathWithDifferentToken()
    {
        Container::getInstance()->make(ParallelTesting::class)->resolveTokenUsing(fn () => '42');

        Container::getInstance()['config']->set('view.compiled', '/var/www/storage/views');

        $this->assertSame('/var/www/storage/views/test_42', $this->testCompiledViewPath());
    }

    public function testCompiledViewPathReturnsNullWhenEmpty()
    {
        Container::getInstance()['config']->set('view.compiled', '');

        $this->assertNull($this->testCompiledViewPath());
    }

    public function testSwitchToCompiledViewPathUpdatesConfig()
    {
        $this->switchToCompiledViewPath('/new/compiled/path');

        $this->assertSame('/new/compiled/path', Container::getInstance()['config']->get('view.compiled'));
    }

    public function testSwitchToCompiledViewPathUpdatesCompilerCachePath()
    {
        $container = Container::getInstance();
        $compiler = new BladeCompiler(m::mock(Filesystem::class), '/original/path');

        $container->instance('blade.compiler', $compiler);

        $this->switchToCompiledViewPath('/new/compiled/path');

        $this->assertSame('/new/compiled/path', $container['config']->get('view.compiled'));
        $this->assertSame('/new/compiled/path', (new ReflectionProperty($compiler, 'cachePath'))->getValue($compiler));
    }

    public function testCompiledViewPath()
    {
        $instance = new class
        {
            use TestViews;

            public $app;

            public function __construct()
            {
                $this->app = Container::getInstance();
            }
        };

        (new ReflectionProperty($instance::class, 'originalCompiledViewPath'))->setValue(null, null);

        $method = new ReflectionMethod($instance, 'parallelSafeCompiledViewPath');

        return $method->invoke($instance);
    }

    public function testTearDownProcessDeletesCompiledViewDirectory()
    {
        Container::getInstance()->make(ParallelTesting::class)->resolveTokenUsing(fn () => '7');

        $instance = new class
        {
            use TestViews;

            public $app;

            public function __construct()
            {
                $this->app = Container::getInstance();
            }
        };

        (new ReflectionProperty($instance::class, 'originalCompiledViewPath'))->setValue(null, null);

        $method = new ReflectionMethod($instance, 'bootTestViews');
        $method->invoke($instance);

        $parallelTesting = Container::getInstance()->make(ParallelTesting::class);
        $tearDownCallbacks = (new ReflectionProperty($parallelTesting, 'tearDownProcessCallbacks'))->getValue($parallelTesting);

        $this->assertCount(1, $tearDownCallbacks);
    }

    public function switchToCompiledViewPath($path)
    {
        $instance = new class
        {
            use TestViews;

            public $app;

            public function __construct()
            {
                $this->app = Container::getInstance();
            }
        };

        $method = new ReflectionMethod($instance, 'switchToCompiledViewPath');
        $method->invoke($instance, $path);
    }
}
