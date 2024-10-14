<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use ErrorException;
use Illuminate\Config\Repository as Config;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Log\LogManager;
use Illuminate\Support\Env;
use Mockery as m;
use Monolog\Handler\NullHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

class HandleExceptionsTest extends TestCase
{
    protected $app;
    protected $config;

    protected function setUp(): void
    {
        $this->app = m::mock(Application::setInstance(new Application));

        $this->app->instance('config', $this->config = new Config());
    }

    protected function handleExceptions()
    {
        return tap(new HandleExceptions(), function ($instance) {
            with(new ReflectionClass($instance), function ($reflection) use ($instance) {
                $reflection->getProperty('app')->setValue($instance, $this->app);
            });
        });
    }

    protected function tearDown(): void
    {
        Application::setInstance(null);
        HandleExceptions::flushState();

        m::close();
    }

    public function testPhpDeprecations()
    {
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $logger->expects('channel')->with('deprecations')->andReturnSelf();
        $logger->expects('warning')->with(sprintf('%s in %s on line %s',
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        ));

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );
    }

    public function testPhpDeprecationsWithStackTraces()
    {
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $this->config->set('logging.deprecations', [
            'channel' => 'null',
            'trace' => true,
        ]);

        $logger->expects('channel')->with('deprecations')->andReturnSelf();
        $logger->expects('warning')->with(
            m::on(fn (string $message) => (bool) preg_match(
                <<<REGEXP
                #ErrorException: str_contains\(\): Passing null to parameter \#2 \(\\\$needle\) of type string is deprecated in /home/user/laravel/routes/web\.php:17
                Stack trace:
                \#0 .*helpers.php\(.*\): Illuminate\\\\Foundation\\\\Bootstrap\\\\HandleExceptions.*
                \#1 .*HandleExceptions\.php\(.*\): with.*
                \#2 .*HandleExceptions\.php\(.*\): Illuminate\\\\Foundation\\\\Bootstrap\\\\HandleExceptions->handleDeprecation.*
                \#3 .*HandleExceptionsTest\.php\(.*\): Illuminate\\\\Foundation\\\\Bootstrap\\\\HandleExceptions->handleError.*
                [\s\S]*#i
                REGEXP,
                $message
            ))
        );

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );
    }

    public function testNullValueAsChannelUsesNullDriver()
    {
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $this->config->set('logging.deprecations', [
            'channel' => null,
            'trace' => false,
        ]);

        $logger->expects('channel')->with('deprecations')->andReturnSelf();
        $logger->expects('warning')->with(sprintf('%s in %s on line %s',
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        ));

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
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $logger->expects('channel')->with('deprecations')->andReturnSelf();
        $logger->expects('warning')->with(sprintf('%s in %s on line %s',
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        ));

        $this->handleExceptions()->handleError(
            E_USER_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );
    }

    public function testUserDeprecationsWithStackTraces()
    {
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $this->config->set('logging.deprecations', [
            'channel' => 'null',
            'trace' => true,
        ]);

        $logger->expects('channel')->with('deprecations')->andReturnSelf();
        $logger->expects('warning')->with(
            m::on(fn (string $message) => (bool) preg_match(
                <<<REGEXP
                #ErrorException: str_contains\(\): Passing null to parameter \#2 \(\\\$needle\) of type string is deprecated in /home/user/laravel/routes/web\.php:17
                Stack trace:
                \#0 .*helpers.php\(.*\): Illuminate\\\\Foundation\\\\Bootstrap\\\\HandleExceptions.*
                \#1 .*HandleExceptions\.php\(.*\): with.*
                \#2 .*HandleExceptions\.php\(.*\): Illuminate\\\\Foundation\\\\Bootstrap\\\\HandleExceptions->handleDeprecation.*
                \#3 .*HandleExceptionsTest\.php\(.*\): Illuminate\\\\Foundation\\\\Bootstrap\\\\HandleExceptions->handleError.*
                [\s\S]*#i
                REGEXP,
                $message
            ))
        );

        $this->handleExceptions()->handleError(
            E_USER_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );
    }

    public function testErrors()
    {
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);

        $logger->shouldNotReceive('channel');
        $logger->shouldNotReceive('warning');

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Something went wrong');

        $this->handleExceptions()->handleError(
            E_ERROR,
            'Something went wrong',
            '/home/user/laravel/src/Providers/AppServiceProvider.php',
            17
        );
    }

    public function testEnsuresDeprecationsDriver()
    {
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $logger->expects('channel')->andReturnSelf();
        $logger->expects('warning');

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
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $logger->expects('channel')->andReturnSelf();
        $logger->expects('warning');

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
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $logger->expects('channel')->andReturnSelf();
        $logger->expects('warning');

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
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $logger->expects('channel')->andReturnSelf();
        $logger->expects('warning');

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
        $this->app->expects('runningUnitTests')->andReturn(false);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );
    }

    public function testItIgnoreDeprecationLoggingWhenRunningUnitTests()
    {
        $resolved = false;
        $this->app->bind(LogManager::class, function () use (&$resolved) {
            $resolved = true;

            throw new RuntimeException();
        });
        $this->app->expects('runningUnitTests')->andReturn(true);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

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
        $logger = m::mock(LogManager::class);
        $logger->expects('channel')->andReturnSelf();
        $logger->expects('warning');
        $this->app->instance(LogManager::class, $logger);
        $this->app->expects('runningUnitTests')->andReturn(true);
        $this->app->expects('hasBeenBootstrapped')->andReturn(true);

        Env::getRepository()->set('LOG_DEPRECATIONS_WHILE_TESTING', true);

        $this->handleExceptions()->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );
    }

    public function testForgetApp()
    {
        $instance = $this->handleExceptions();

        $appResolver = fn () => with(new ReflectionClass($instance), function ($reflection) use ($instance) {
            $property = $reflection->getProperty('app');

            return $property->getValue($instance);
        });

        $this->assertNotNull($appResolver());

        HandleExceptions::forgetApp();

        $this->assertNull($appResolver());
    }

    public function testHandlerForgetsPreviousApp()
    {
        $instance = $this->handleExceptions();

        $appResolver = fn () => with(new ReflectionClass($instance), function ($reflection) use ($instance) {
            $property = $reflection->getProperty('app');

            return $property->getValue($instance);
        });

        $this->assertSame($this->app, $appResolver());

        $instance->bootstrap($newApp = tap(m::mock(Application::class), function ($app) {
            $app->expects('environment')->andReturn(true);
        }));

        $this->assertNotSame($this->app, $appResolver());
        $this->assertSame($newApp, $appResolver());
    }
}

class CustomNullHandler extends NullHandler
{
}
