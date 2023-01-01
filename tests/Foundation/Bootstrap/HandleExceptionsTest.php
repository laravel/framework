<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use ErrorException;
use Illuminate\Config\Repository as Config;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Log\LogManager;
use Mockery as m;
use Monolog\Handler\NullHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HandleExceptionsTest extends TestCase
{
    protected $app;
    protected $config;
    protected $handleExceptions;

    protected function setUp(): void
    {
        $this->app = m::mock(Application::setInstance(new Application));

        $this->config = new Config();

        $this->app->singleton('config', function () {
            return $this->config;
        });

        $this->handleExceptions = new HandleExceptions();

        with(new ReflectionClass($this->handleExceptions), function ($reflection) {
            $property = tap($reflection->getProperty('app'))->setAccessible(true);

            $property->setValue(
                $this->handleExceptions,
                tap($this->app, function ($app) {
                    $app->shouldReceive('runningUnitTests')->andReturn(false);
                    $app->shouldReceive('hasBeenBootstrapped')->andReturn(true);
                })
            );
        });
    }

    protected function tearDown(): void
    {
        Application::setInstance(null);
    }

    public function testPhpDeprecations()
    {
        $logger = m::mock(LogManager::class);
        $this->app->instance(LogManager::class, $logger);

        $logger->shouldReceive('channel')->with('deprecations')->andReturnSelf();
        $logger->shouldReceive('warning')->with(sprintf('%s in %s on line %s',
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        ));

        $this->handleExceptions->handleError(
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

        $this->config->set('logging.deprecations', [
            'channel' => 'null',
            'trace' => true,
        ]);

        $logger->shouldReceive('channel')->with('deprecations')->andReturnSelf();
        $logger->shouldReceive('warning')->with(
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

        $this->handleExceptions->handleError(
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

        $this->config->set('logging.deprecations', [
            'channel' => null,
            'trace' => false,
        ]);

        $logger->shouldReceive('channel')->with('deprecations')->andReturnSelf();
        $logger->shouldReceive('warning')->with(sprintf('%s in %s on line %s',
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        ));

        $this->handleExceptions->handleError(
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

        $logger->shouldReceive('channel')->with('deprecations')->andReturnSelf();
        $logger->shouldReceive('warning')->with(sprintf('%s in %s on line %s',
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        ));

        $this->handleExceptions->handleError(
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

        $this->config->set('logging.deprecations', [
            'channel' => 'null',
            'trace' => true,
        ]);

        $logger->shouldReceive('channel')->with('deprecations')->andReturnSelf();
        $logger->shouldReceive('warning')->with(
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

        $this->handleExceptions->handleError(
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

        $this->handleExceptions->handleError(
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

        $logger->shouldReceive('channel')->andReturnSelf();
        $logger->shouldReceive('warning');

        $this->config->set('logging.channels.stack', [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ]);
        $this->config->set('logging.deprecations', 'stack');

        $this->handleExceptions->handleError(
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

        $logger->shouldReceive('channel')->andReturnSelf();
        $logger->shouldReceive('warning');

        $this->handleExceptions->handleError(
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

        $logger->shouldReceive('channel')->andReturnSelf();
        $logger->shouldReceive('warning');

        $this->handleExceptions->handleError(
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

        $logger->shouldReceive('channel')->andReturnSelf();
        $logger->shouldReceive('warning');

        $this->config->set('logging.channels.null', [
            'driver' => 'monolog',
            'handler' => CustomNullHandler::class,
        ]);

        $this->handleExceptions->handleError(
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
        $this->handleExceptions->handleError(
            E_DEPRECATED,
            'str_contains(): Passing null to parameter #2 ($needle) of type string is deprecated',
            '/home/user/laravel/routes/web.php',
            17
        );
    }

    public function testForgetApp()
    {
        $appResolver = fn () => with(new ReflectionClass($this->handleExceptions), function ($reflection) {
            $property = tap($reflection->getProperty('app'))->setAccessible(true);

            return $property->getValue($this->handleExceptions);
        });

        $this->assertNotNull($appResolver());

        handleExceptions::forgetApp();

        $this->assertNull($appResolver());
    }

    public function testHandlerForgetsPreviousApp()
    {
        $appResolver = fn () => with(new ReflectionClass($this->handleExceptions), function ($reflection) {
            $property = tap($reflection->getProperty('app'))->setAccessible(true);

            return $property->getValue($this->handleExceptions);
        });

        $this->assertSame($this->app, $appResolver());

        $this->handleExceptions->bootstrap($newApp = tap(m::mock(Application::class), function ($app) {
            $app->shouldReceive('environment')->once()->andReturn(true);
        }));

        $this->assertNotSame($this->app, $appResolver());
        $this->assertSame($newApp, $appResolver());
    }
}

class CustomNullHandler extends NullHandler
{
}
