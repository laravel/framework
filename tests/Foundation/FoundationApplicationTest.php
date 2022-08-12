<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Support\ServiceProvider;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class FoundationApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSetLocaleSetsLocaleAndFiresLocaleChangedEvent()
    {
        $app = new Application;
        $app['config'] = $config = m::mock(stdClass::class);
        $config->shouldReceive('set')->once()->with('app.locale', 'foo');
        $app['translator'] = $trans = m::mock(stdClass::class);
        $trans->shouldReceive('setLocale')->once()->with('foo');
        $app['events'] = $events = m::mock(stdClass::class);
        $events->shouldReceive('dispatch')->once()->with(m::type(LocaleUpdated::class));

        $app->setLocale('foo');
    }

    public function testServiceProvidersAreCorrectlyRegistered()
    {
        $provider = m::mock(ApplicationBasicServiceProviderStub::class);
        $class = get_class($provider);
        $provider->shouldReceive('register')->once();
        $app = new Application;
        $app->register($provider);

        $this->assertArrayHasKey($class, $app->getLoadedProviders());
    }

    public function testClassesAreBoundWhenServiceProviderIsRegistered()
    {
        $app = new Application;
        $app->register($provider = new class($app) extends ServiceProvider
        {
            public $bindings = [
                AbstractClass::class => ConcreteClass::class,
            ];
        });

        $this->assertArrayHasKey(get_class($provider), $app->getLoadedProviders());

        $instance = $app->make(AbstractClass::class);

        $this->assertInstanceOf(ConcreteClass::class, $instance);
        $this->assertNotSame($instance, $app->make(AbstractClass::class));
    }

    public function testSingletonsAreCreatedWhenServiceProviderIsRegistered()
    {
        $app = new Application;
        $app->register($provider = new class($app) extends ServiceProvider
        {
            public $singletons = [
                AbstractClass::class => ConcreteClass::class,
            ];
        });

        $this->assertArrayHasKey(get_class($provider), $app->getLoadedProviders());

        $instance = $app->make(AbstractClass::class);

        $this->assertInstanceOf(ConcreteClass::class, $instance);
        $this->assertSame($instance, $app->make(AbstractClass::class));
    }

    public function testServiceProvidersAreCorrectlyRegisteredWhenRegisterMethodIsNotFilled()
    {
        $provider = m::mock(ServiceProvider::class);
        $class = get_class($provider);
        $provider->shouldReceive('register')->once();
        $app = new Application;
        $app->register($provider);

        $this->assertArrayHasKey($class, $app->getLoadedProviders());
    }

    public function testServiceProvidersCouldBeLoaded()
    {
        $provider = m::mock(ServiceProvider::class);
        $class = get_class($provider);
        $provider->shouldReceive('register')->once();
        $app = new Application;
        $app->register($provider);

        $this->assertTrue($app->providerIsLoaded($class));
        $this->assertFalse($app->providerIsLoaded(ApplicationBasicServiceProviderStub::class));
    }

    public function testDeferredServicesMarkedAsBound()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => ApplicationDeferredServiceProviderStub::class]);
        $this->assertTrue($app->bound('foo'));
        $this->assertSame('foo', $app->make('foo'));
    }

    public function testDeferredServicesAreSharedProperly()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => ApplicationDeferredSharedServiceProviderStub::class]);
        $this->assertTrue($app->bound('foo'));
        $one = $app->make('foo');
        $two = $app->make('foo');
        $this->assertInstanceOf(stdClass::class, $one);
        $this->assertInstanceOf(stdClass::class, $two);
        $this->assertSame($one, $two);
    }

    public function testDeferredServicesCanBeExtended()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => ApplicationDeferredServiceProviderStub::class]);
        $app->extend('foo', function ($instance, $container) {
            return $instance.'bar';
        });
        $this->assertSame('foobar', $app->make('foo'));
    }

    public function testDeferredServiceProviderIsRegisteredOnlyOnce()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => ApplicationDeferredServiceProviderCountStub::class]);
        $obj = $app->make('foo');
        $this->assertInstanceOf(stdClass::class, $obj);
        $this->assertSame($obj, $app->make('foo'));
        $this->assertEquals(1, ApplicationDeferredServiceProviderCountStub::$count);
    }

    public function testDeferredServiceDontRunWhenInstanceSet()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => ApplicationDeferredServiceProviderStub::class]);
        $app->instance('foo', 'bar');
        $instance = $app->make('foo');
        $this->assertSame('bar', $instance);
    }

    public function testDeferredServicesAreLazilyInitialized()
    {
        ApplicationDeferredServiceProviderStub::$initialized = false;
        $app = new Application;
        $app->setDeferredServices(['foo' => ApplicationDeferredServiceProviderStub::class]);
        $this->assertTrue($app->bound('foo'));
        $this->assertFalse(ApplicationDeferredServiceProviderStub::$initialized);
        $app->extend('foo', function ($instance, $container) {
            return $instance.'bar';
        });
        $this->assertFalse(ApplicationDeferredServiceProviderStub::$initialized);
        $this->assertSame('foobar', $app->make('foo'));
        $this->assertTrue(ApplicationDeferredServiceProviderStub::$initialized);
    }

    public function testDeferredServicesCanRegisterFactories()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => ApplicationFactoryProviderStub::class]);
        $this->assertTrue($app->bound('foo'));
        $this->assertEquals(1, $app->make('foo'));
        $this->assertEquals(2, $app->make('foo'));
        $this->assertEquals(3, $app->make('foo'));
    }

    public function testSingleProviderCanProvideMultipleDeferredServices()
    {
        $app = new Application;
        $app->setDeferredServices([
            'foo' => ApplicationMultiProviderStub::class,
            'bar' => ApplicationMultiProviderStub::class,
        ]);
        $this->assertSame('foo', $app->make('foo'));
        $this->assertSame('foobar', $app->make('bar'));
    }

    public function testDeferredServiceIsLoadedWhenAccessingImplementationThroughInterface()
    {
        $app = new Application;
        $app->setDeferredServices([
            SampleInterface::class => InterfaceToImplementationDeferredServiceProvider::class,
            SampleImplementation::class => SampleImplementationDeferredServiceProvider::class,
        ]);
        $instance = $app->make(SampleInterface::class);
        $this->assertSame('foo', $instance->getPrimitive());
    }

    public function testEnvironment()
    {
        $app = new Application;
        $app['env'] = 'foo';

        $this->assertSame('foo', $app->environment());

        $this->assertTrue($app->environment('foo'));
        $this->assertTrue($app->environment('f*'));
        $this->assertTrue($app->environment('foo', 'bar'));
        $this->assertTrue($app->environment(['foo', 'bar']));

        $this->assertFalse($app->environment('qux'));
        $this->assertFalse($app->environment('q*'));
        $this->assertFalse($app->environment('qux', 'bar'));
        $this->assertFalse($app->environment(['qux', 'bar']));
    }

    public function testEnvironmentHelpers()
    {
        $local = new Application;
        $local['env'] = 'local';

        $this->assertTrue($local->isLocal());
        $this->assertFalse($local->isProduction());
        $this->assertFalse($local->runningUnitTests());

        $production = new Application;
        $production['env'] = 'production';

        $this->assertTrue($production->isProduction());
        $this->assertFalse($production->isLocal());
        $this->assertFalse($production->runningUnitTests());

        $testing = new Application;
        $testing['env'] = 'testing';

        $this->assertTrue($testing->runningUnitTests());
        $this->assertFalse($testing->isLocal());
        $this->assertFalse($testing->isProduction());
    }

    public function testDebugHelper()
    {
        $debugOff = new Application;
        $debugOff['config'] = new Repository(['app' => ['debug' => false]]);

        $this->assertFalse($debugOff->hasDebugModeEnabled());

        $debugOn = new Application;
        $debugOn['config'] = new Repository(['app' => ['debug' => true]]);

        $this->assertTrue($debugOn->hasDebugModeEnabled());
    }

    public function testMethodAfterLoadingEnvironmentAddsClosure()
    {
        $app = new Application;
        $closure = function () {
            //
        };
        $app->afterLoadingEnvironment($closure);
        $this->assertArrayHasKey(0, $app['events']->getListeners('bootstrapped: Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables'));
    }

    public function testBeforeBootstrappingAddsClosure()
    {
        $app = new Application;
        $closure = function () {
            //
        };
        $app->beforeBootstrapping(RegisterFacades::class, $closure);
        $this->assertArrayHasKey(0, $app['events']->getListeners('bootstrapping: Illuminate\Foundation\Bootstrap\RegisterFacades'));
    }

    public function testTerminationTests()
    {
        $app = new Application;

        $result = [];
        $callback1 = function () use (&$result) {
            $result[] = 1;
        };

        $callback2 = function () use (&$result) {
            $result[] = 2;
        };

        $callback3 = function () use (&$result) {
            $result[] = 3;
        };

        $app->terminating($callback1);
        $app->terminating($callback2);
        $app->terminating($callback3);

        $app->terminate();

        $this->assertEquals([1, 2, 3], $result);
    }

    public function testAfterBootstrappingAddsClosure()
    {
        $app = new Application;
        $closure = function () {
            //
        };
        $app->afterBootstrapping(RegisterFacades::class, $closure);
        $this->assertArrayHasKey(0, $app['events']->getListeners('bootstrapped: Illuminate\Foundation\Bootstrap\RegisterFacades'));
    }

    public function testTerminationCallbacksCanAcceptAtNotation()
    {
        $app = new Application;
        $app->terminating(ConcreteTerminator::class.'@terminate');

        $app->terminate();

        $this->assertEquals(1, ConcreteTerminator::$counter);
    }

    public function testBootingCallbacks()
    {
        $application = new Application;

        $counter = 0;
        $closure = function ($app) use (&$counter, $application) {
            $counter++;
            $this->assertSame($application, $app);
        };

        $closure2 = function ($app) use (&$counter, $application) {
            $counter++;
            $this->assertSame($application, $app);
        };

        $application->booting($closure);
        $application->booting($closure2);

        $application->boot();

        $this->assertEquals(2, $counter);
    }

    public function testBootedCallbacks()
    {
        $application = new Application;

        $counter = 0;
        $closure = function ($app) use (&$counter, $application) {
            $counter++;
            $this->assertSame($application, $app);
        };

        $closure2 = function ($app) use (&$counter, $application) {
            $counter++;
            $this->assertSame($application, $app);
        };

        $closure3 = function ($app) use (&$counter, $application) {
            $counter++;
            $this->assertSame($application, $app);
        };

        $application->booting($closure);
        $application->booted($closure);
        $application->booted($closure2);
        $application->boot();

        $this->assertEquals(3, $counter);

        $application->booted($closure3);

        $this->assertEquals(4, $counter);
    }

    public function testGetNamespace()
    {
        $app1 = new Application(realpath(__DIR__.'/fixtures/laravel1'));
        $app2 = new Application(realpath(__DIR__.'/fixtures/laravel2'));

        $this->assertSame('Laravel\\One\\', $app1->getNamespace());
        $this->assertSame('Laravel\\Two\\', $app2->getNamespace());
    }

    public function testCachePathsResolveToBootstrapCacheDirectory()
    {
        $app = new Application('/base/path');

        $ds = DIRECTORY_SEPARATOR;
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/services.php', $app->getCachedServicesPath());
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/packages.php', $app->getCachedPackagesPath());
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/config.php', $app->getCachedConfigPath());
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/routes-v7.php', $app->getCachedRoutesPath());
        $this->assertSame('/base/path'.$ds.'bootstrap'.$ds.'cache/events.php', $app->getCachedEventsPath());
    }

    public function testEnvPathsAreUsedForCachePathsWhenSpecified()
    {
        $app = new Application('/base/path');
        $_SERVER['APP_SERVICES_CACHE'] = '/absolute/path/services.php';
        $_SERVER['APP_PACKAGES_CACHE'] = '/absolute/path/packages.php';
        $_SERVER['APP_CONFIG_CACHE'] = '/absolute/path/config.php';
        $_SERVER['APP_ROUTES_CACHE'] = '/absolute/path/routes.php';
        $_SERVER['APP_EVENTS_CACHE'] = '/absolute/path/events.php';

        $this->assertSame('/absolute/path/services.php', $app->getCachedServicesPath());
        $this->assertSame('/absolute/path/packages.php', $app->getCachedPackagesPath());
        $this->assertSame('/absolute/path/config.php', $app->getCachedConfigPath());
        $this->assertSame('/absolute/path/routes.php', $app->getCachedRoutesPath());
        $this->assertSame('/absolute/path/events.php', $app->getCachedEventsPath());

        unset(
            $_SERVER['APP_SERVICES_CACHE'],
            $_SERVER['APP_PACKAGES_CACHE'],
            $_SERVER['APP_CONFIG_CACHE'],
            $_SERVER['APP_ROUTES_CACHE'],
            $_SERVER['APP_EVENTS_CACHE']
        );
    }

    public function testEnvPathsAreUsedAndMadeAbsoluteForCachePathsWhenSpecifiedAsRelative()
    {
        $app = new Application('/base/path');
        $_SERVER['APP_SERVICES_CACHE'] = 'relative/path/services.php';
        $_SERVER['APP_PACKAGES_CACHE'] = 'relative/path/packages.php';
        $_SERVER['APP_CONFIG_CACHE'] = 'relative/path/config.php';
        $_SERVER['APP_ROUTES_CACHE'] = 'relative/path/routes.php';
        $_SERVER['APP_EVENTS_CACHE'] = 'relative/path/events.php';

        $ds = DIRECTORY_SEPARATOR;
        $this->assertSame('/base/path'.$ds.'relative/path/services.php', $app->getCachedServicesPath());
        $this->assertSame('/base/path'.$ds.'relative/path/packages.php', $app->getCachedPackagesPath());
        $this->assertSame('/base/path'.$ds.'relative/path/config.php', $app->getCachedConfigPath());
        $this->assertSame('/base/path'.$ds.'relative/path/routes.php', $app->getCachedRoutesPath());
        $this->assertSame('/base/path'.$ds.'relative/path/events.php', $app->getCachedEventsPath());

        unset(
            $_SERVER['APP_SERVICES_CACHE'],
            $_SERVER['APP_PACKAGES_CACHE'],
            $_SERVER['APP_CONFIG_CACHE'],
            $_SERVER['APP_ROUTES_CACHE'],
            $_SERVER['APP_EVENTS_CACHE']
        );
    }

    public function testEnvPathsAreUsedAndMadeAbsoluteForCachePathsWhenSpecifiedAsRelativeWithNullBasePath()
    {
        $app = new Application;
        $_SERVER['APP_SERVICES_CACHE'] = 'relative/path/services.php';
        $_SERVER['APP_PACKAGES_CACHE'] = 'relative/path/packages.php';
        $_SERVER['APP_CONFIG_CACHE'] = 'relative/path/config.php';
        $_SERVER['APP_ROUTES_CACHE'] = 'relative/path/routes.php';
        $_SERVER['APP_EVENTS_CACHE'] = 'relative/path/events.php';

        $ds = DIRECTORY_SEPARATOR;
        $this->assertSame($ds.'relative/path/services.php', $app->getCachedServicesPath());
        $this->assertSame($ds.'relative/path/packages.php', $app->getCachedPackagesPath());
        $this->assertSame($ds.'relative/path/config.php', $app->getCachedConfigPath());
        $this->assertSame($ds.'relative/path/routes.php', $app->getCachedRoutesPath());
        $this->assertSame($ds.'relative/path/events.php', $app->getCachedEventsPath());

        unset(
            $_SERVER['APP_SERVICES_CACHE'],
            $_SERVER['APP_PACKAGES_CACHE'],
            $_SERVER['APP_CONFIG_CACHE'],
            $_SERVER['APP_ROUTES_CACHE'],
            $_SERVER['APP_EVENTS_CACHE']
        );
    }

    public function testEnvPathsAreAbsoluteInWindows()
    {
        $app = new Application(__DIR__);
        $app->addAbsoluteCachePathPrefix('C:');
        $_SERVER['APP_SERVICES_CACHE'] = 'C:\framework\services.php';
        $_SERVER['APP_PACKAGES_CACHE'] = 'C:\framework\packages.php';
        $_SERVER['APP_CONFIG_CACHE'] = 'C:\framework\config.php';
        $_SERVER['APP_ROUTES_CACHE'] = 'C:\framework\routes.php';
        $_SERVER['APP_EVENTS_CACHE'] = 'C:\framework\events.php';

        $this->assertSame('C:\framework\services.php', $app->getCachedServicesPath());
        $this->assertSame('C:\framework\packages.php', $app->getCachedPackagesPath());
        $this->assertSame('C:\framework\config.php', $app->getCachedConfigPath());
        $this->assertSame('C:\framework\routes.php', $app->getCachedRoutesPath());
        $this->assertSame('C:\framework\events.php', $app->getCachedEventsPath());

        unset(
            $_SERVER['APP_SERVICES_CACHE'],
            $_SERVER['APP_PACKAGES_CACHE'],
            $_SERVER['APP_CONFIG_CACHE'],
            $_SERVER['APP_ROUTES_CACHE'],
            $_SERVER['APP_EVENTS_CACHE']
        );
    }
}

class ApplicationBasicServiceProviderStub extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register()
    {
        //
    }
}

class ApplicationDeferredSharedServiceProviderStub extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton('foo', function () {
            return new stdClass;
        });
    }
}

class ApplicationDeferredServiceProviderCountStub extends ServiceProvider implements DeferrableProvider
{
    public static $count = 0;

    public function register()
    {
        static::$count++;
        $this->app['foo'] = new stdClass;
    }
}

class ApplicationDeferredServiceProviderStub extends ServiceProvider implements DeferrableProvider
{
    public static $initialized = false;

    public function register()
    {
        static::$initialized = true;
        $this->app['foo'] = 'foo';
    }
}

interface SampleInterface
{
    public function getPrimitive();
}

class SampleImplementation implements SampleInterface
{
    private $primitive;

    public function __construct($primitive)
    {
        $this->primitive = $primitive;
    }

    public function getPrimitive()
    {
        return $this->primitive;
    }
}

class InterfaceToImplementationDeferredServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->bind(SampleInterface::class, SampleImplementation::class);
    }
}

class SampleImplementationDeferredServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->when(SampleImplementation::class)->needs('$primitive')->give(function () {
            return 'foo';
        });
    }
}

class ApplicationFactoryProviderStub extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->bind('foo', function () {
            static $count = 0;

            return ++$count;
        });
    }
}

class ApplicationMultiProviderStub extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton('foo', function () {
            return 'foo';
        });
        $this->app->singleton('bar', function ($app) {
            return $app['foo'].'bar';
        });
    }
}

abstract class AbstractClass
{
    //
}

class ConcreteClass extends AbstractClass
{
    //
}

class ConcreteTerminator
{
    public static $counter = 0;

    public function terminate()
    {
        return self::$counter++;
    }
}
