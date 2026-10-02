<?php

namespace Illuminate\Tests\Testing\Concerns;

use ErrorException;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\ParallelTesting as ParallelTestingFacade;
use Illuminate\Testing\Concerns\TestViews;
use Illuminate\Testing\ParallelTesting;
use Illuminate\View\Compilers\BladeCompiler;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class TestViewsTest extends TestCase
{
    protected function setUp(): void
    {
        Container::setInstance($container = new Container);

        Facade::setFacadeApplication($container);
        ParallelTestingFacade::clearResolvedInstance();

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
        $compiler = new BladeCompiler(Mockery::mock(Filesystem::class), '/original/path');

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

    public function testSetUpProcessToleratesDirectoryCreatedByConcurrentRun()
    {
        $compiled = sys_get_temp_dir().'/laravel-test-views-'.uniqid();
        $path = $compiled.'/test_9';

        // Another parallel run with the same worker token created the directory after this run checked for it...
        mkdir($path, 0755, true);

        $this->bootTestViewsUsingFilesystem($compiled, '9', new class extends Filesystem
        {
            public function isDirectory($directory)
            {
                return false;
            }
        });

        try {
            $this->withWarningsAsExceptions(
                fn () => Container::getInstance()->make(ParallelTesting::class)->callSetUpProcessCallbacks()
            );

            $this->assertDirectoryExists($path);
        } finally {
            (new Filesystem)->deleteDirectory($compiled);
        }
    }

    public function testTearDownProcessToleratesDirectoryRemovedByConcurrentRun()
    {
        $compiled = sys_get_temp_dir().'/laravel-test-views-'.uniqid();
        $path = $compiled.'/test_9';

        // Another parallel run with the same worker token removed the directory after this run checked for it...
        $this->bootTestViewsUsingFilesystem($compiled, '9', new class extends Filesystem
        {
            public function isDirectory($directory)
            {
                return true;
            }
        });

        $this->withWarningsAsExceptions(
            fn () => Container::getInstance()->make(ParallelTesting::class)->callTearDownProcessCallbacks()
        );

        $this->assertDirectoryDoesNotExist($path);
    }

    protected function bootTestViewsUsingFilesystem($compiled, $token, Filesystem $files)
    {
        $container = Container::getInstance();

        $container['config']->set('view.compiled', $compiled);
        $container->make(ParallelTesting::class)->resolveTokenUsing(fn () => $token);
        $container->instance('files', $files);

        File::clearResolvedInstance();

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

        (new ReflectionMethod($instance, 'bootTestViews'))->invoke($instance);
    }

    protected function withWarningsAsExceptions(callable $callback)
    {
        $reporting = error_reporting();

        set_error_handler(function ($severity, $message, $file, $line) use ($reporting) {
            // A changed level means the warning was silenced with the @ operator...
            if (error_reporting() !== $reporting) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $callback();
        } finally {
            restore_error_handler();
            File::clearResolvedInstance();
        }
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
