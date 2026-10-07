<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use JMac\Testing\Double;
use Error;
use ErrorException;
use Illuminate\Config\Repository as Config;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;
use Illuminate\Support\Env;
use Mockery;
use Monolog\Handler\NullHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

class HandleExceptionsTest extends TestCase
{
    protected $app;
    protected $config;

    protected function setUp(): void
    {
        $this->app = Application::setInstance(new Application);

        $this->app->instance('config', $this->config = new Config());

        (new ReflectionClass(Application::class))
            ->getProperty('hasBeenBootstrapped')
            ->setValue($this->app, true);
    }

    protected function handleExceptions()
    {
        return tap(new HandleExceptions(), function ($instance) {
            (new ReflectionClass($instance))->getProperty('app')->setValue($instance, $this->app);
        });
    }

    protected function getRealLogger(): TestHandler
    {
        $handler = new TestHandler;

        $manager = new LogManager($this->app);
        $manager->extend('capture', fn () => new Logger(tap(new Monolog('testbench'), fn ($monolog) => $monolog->pushHandler($handler))));

        $this->config->set('logging.channels.null', ['driver' => 'capture']);
        $this->app->instance(LogManager::class, $manager);

        return $handler;
    }

    protected function tearDown(): void
    {
        Application::setInstance(null);
        HandleExceptions::flushState($this);
        Env::getRepository()->clear('LOG_DEPRECATIONS_WHILE_TESTING');
    }

    public function testPhpDeprecations()
    {
        $handler = $this->getRealLogger();

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertTrue($handler->hasWarningThatContains(sprintf('%s in %s on line %s',
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        )));
    }

    public function testPhpDeprecationsWithStackTraces()
    {
        $handler = $this->getRealLogger();

        $this->config->set('logging.deprecations', [
            'channel' => 'null',
            'trace' => true,
        ]);

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertTrue($handler->hasWarningThatContains('str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated'));

        $exception = $handler->getRecords()[0]->context['exception'];

        $this->assertInstanceOf(ErrorException::class, $exception);
        $this->assertSame(E_DEPRECATED, $exception->getSeverity());
        $this->assertSame('/home/user/laravel/routes/web.php', $exception->getFile());
        $this->assertSame(17, $exception->getLine());
        $this->assertNotEmpty($exception->getTrace());
    }

    public function testNullValueAsChannelUsesNullDriver()
    {
        $this->app->instance(LogManager::class, new LogManager($this->app));

        $this->config->set('logging.deprecations', [
            'channel' => null,
            'trace' => false,
        ]);

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertEquals([
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ], $this->config->get('logging.channels.deprecations'));
    }

    public function testUserDeprecations()
    {
        $handler = $this->getRealLogger();

        $this->handleExceptions()->handleError(
            E_USER_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertTrue($handler->hasWarningThatContains(sprintf('%s in %s on line %s',
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        )));
    }

    public function testUserDeprecationsWithStackTraces()
    {
        $handler = $this->getRealLogger();

        $this->config->set('logging.deprecations', [
            'channel' => 'null',
            'trace' => true,
        ]);

        $this->handleExceptions()->handleError(
            E_USER_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertTrue($handler->hasWarningThatContains('str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated'));

        $exception = $handler->getRecords()[0]->context['exception'];

        $this->assertInstanceOf(ErrorException::class, $exception);
        $this->assertSame(E_USER_DEPRECATED, $exception->getSeverity());
        $this->assertSame('/home/user/laravel/routes/web.php', $exception->getFile());
        $this->assertSame(17, $exception->getLine());
        $this->assertNotEmpty($exception->getTrace());
    }

    public function testErrors()
    {
        $logger = Double::for(LogManager::class);
        $this->app->instance(LogManager::class, $logger);

        $logger->shouldNotReceive('channel');
        $logger->shouldNotReceive('warning');

        $this->expectExceptionObject(new ErrorException('Something went wrong'));

        $this->handleExceptions()->handleError(
            E_ERROR,
            'Something went wrong',
            '/home/user/laravel/src/Providers/AppServiceProvider.php',
            17
        );
    }

    public function testEnsuresDeprecationsDriver()
    {
        $this->app->instance(LogManager::class, new LogManager($this->app));

        $this->config->set('logging.channels.stack', [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ]);
        $this->config->set('logging.deprecations', 'stack');

        $this->handleExceptions()->handleError(
            E_USER_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertEquals(
            [
                'driver' => 'stack',
                'channels' => ['single'],
                'ignore_exceptions' => false,
            ],
            $this->config->get('logging.channels.deprecations')
        );
    }

    public function testEnsuresNullDeprecationsDriver()
    {
        $this->app->instance(LogManager::class, new LogManager($this->app));

        $this->handleExceptions()->handleError(
            E_USER_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertEquals(
            NullHandler::class,
            $this->config->get('logging.channels.deprecations.handler')
        );
    }

    public function testEnsuresNullLogDriver()
    {
        $this->app->instance(LogManager::class, new LogManager($this->app));

        $this->handleExceptions()->handleError(
            E_USER_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertEquals(
            NullHandler::class,
            $this->config->get('logging.channels.deprecations.handler')
        );
    }

    public function testDoNotOverrideExistingNullLogDriver()
    {
        $this->app->instance(LogManager::class, new LogManager($this->app));

        $this->config->set('logging.channels.null', [
            'driver' => 'monolog',
            'handler' => CustomNullHandler::class,
        ]);

        $this->handleExceptions()->handleError(
            E_USER_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertEquals(
            CustomNullHandler::class,
            $this->config->get('logging.channels.deprecations.handler')
        );
    }

    public function testNoDeprecationsDriverIfNoDeprecationsHereSend()
    {
        $this->assertEquals(null, $this->config->get('logging.deprecations'));
        $this->assertEquals(null, $this->config->get('logging.channels.deprecations'));
    }

    public function testIgnoreDeprecationIfLoggerUnresolvable()
    {
        $this->app->bind(LogManager::class, function () {
            throw new RuntimeException('unresolvable');
        });

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertTrue(true);
    }

    public function testIgnoreDeprecationIfLoggingFails()
    {
        $manager = new LogManager($this->app);
        $manager->extend('capture', fn () => throw new Error('Class "Monolog\Logger" not found'));

        $this->config->set('logging.channels.null', ['driver' => 'capture']);
        $this->app->instance(LogManager::class, $manager);

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertTrue(true);
    }

    public function testItIgnoreDeprecationLoggingWhenRunningUnitTests()
    {
        $resolved = false;
        $this->app->bind(LogManager::class, function () use (&$resolved) {
            $resolved = true;

            throw new RuntimeException();
        });
        $this->app->instance('env', 'testing');

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertFalse($resolved);
    }

    public function testItCanForceViaConfigDeprecationLoggingWhenRunningUnitTests()
    {
        $handler = $this->getRealLogger();
        $this->app->instance('env', 'testing');

        Env::getRepository()->set('LOG_DEPRECATIONS_WHILE_TESTING', true);

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );

        $this->assertTrue($handler->hasWarningRecords());
    }

    public function testForgetApp()
    {
        $instance = $this->handleExceptions();

        $appResolver = fn () => (new ReflectionClass($instance))->getProperty('app')->getValue($instance);

        $this->assertNotNull($appResolver());

        HandleExceptions::forgetApp();

        $this->assertNull($appResolver());
    }

    public function testHandlerForgetsPreviousApp()
    {
        $instance = $this->handleExceptions();

        $appResolver = fn () => (new ReflectionClass($instance))->getProperty('app')->getValue($instance);

        $this->assertSame($this->app, $appResolver());

        $instance->bootstrap($newApp = tap(new Application, fn ($app) => $app->instance('env', 'testing')));

        $this->assertNotSame($this->app, $appResolver());
        $this->assertSame($newApp, $appResolver());
    }

    public function testDeprecationErrorsAreIgnoredWhenAppIsNull()
    {
        $instance = $this->handleExceptions();

        HandleExceptions::forgetApp();

        // Should not throw when static::$app is null (e.g., during Octane request marshaling)
        $instance->handleError(
            E_USER_DEPRECATED,
            'Directly setting property "request" of "Illuminate\Http\Request" is deprecated',
            '/vendor/symfony/http-foundation/Request.php',
            100
        );

        $this->assertTrue(true);
    }
}

class CustomNullHandler extends NullHandler
{
}
