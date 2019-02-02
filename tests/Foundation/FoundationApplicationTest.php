<?php

namespace Illuminate\Tests\Foundation;

use stdClass;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Foundation\Bootstrap\RegisterFacades;

class FoundationApplicationTest extends TestCase
{
    public function tearDown()
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
        $app->register($provider = new class($app) extends ServiceProvider {
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
        $app->register($provider = new class($app) extends ServiceProvider {
            public $singletons = [
                AbstractClass::class => ConcreteClass::class,
            ];
        });

        $this->assertArrayHasKey(get_class($provider), $app->getLoadedProviders());

        $instance = $app->make(AbstractClass::class);

        $this->assertInstanceOf(ConcreteClass::class, $instance);
        $this->assertSame($instance, $app->make(AbstractClass::class));
    }

    public function testServiceProvidersAreCorrectlyRegisteredWhenRegisterMethodIsNotPresent()
    {
        $provider = m::mock(ServiceProvider::class);
        $class = get_class($provider);
        $provider->shouldReceive('register')->never();
        $app = new Application;
        $app->register($provider);

        $this->assertArrayHasKey($class, $app->getLoadedProviders());
    }

    public function testDeferredServicesMarkedAsBound()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => ApplicationDeferredServiceProviderStub::class]);
        $this->assertTrue($app->bound('foo'));
        $this->assertEquals('foo', $app->make('foo'));
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
        $this->assertEquals('foobar', $app->make('foo'));
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
        $this->assertEquals($instance, 'bar');
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
        $this->assertEquals('foobar', $app->make('foo'));
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
        $this->assertEquals('foo', $app->make('foo'));
        $this->assertEquals('foobar', $app->make('bar'));
    }

    public function testEnvironment()
    {
        $app = new Application;
        $app['env'] = 'foo';

        $this->assertEquals('foo', $app->environment());

        $this->assertTrue($app->environment('foo'));
        $this->assertTrue($app->environment('f*'));
        $this->assertTrue($app->environment('foo', 'bar'));
        $this->assertTrue($app->environment(['foo', 'bar']));

        $this->assertFalse($app->environment('qux'));
        $this->assertFalse($app->environment('q*'));
        $this->assertFalse($app->environment('qux', 'bar'));
        $this->assertFalse($app->environment(['qux', 'bar']));
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

        $counter = 0;
        $callback1 = function () use (&$counter) {
            $counter++;
        };

        $callback2 = function () use (&$counter) {
            $counter++;
        };

        $callback3 = function () use (&$counter) {
            $counter++;
        };

        $app->terminating($callback1);
        $app->terminating($callback2);
        $app->terminating($callback3);

        $app->terminate();

        $this->assertEquals(3, $counter);
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

class ApplicationDeferredSharedServiceProviderStub extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton('foo', function () {
            return new stdClass;
        });
    }
}

class ApplicationDeferredServiceProviderCountStub extends ServiceProvider
{
    public static $count = 0;
    protected $defer = true;

    public function register()
    {
        static::$count++;
        $this->app['foo'] = new stdClass;
    }
}

class ApplicationDeferredServiceProviderStub extends ServiceProvider
{
    public static $initialized = false;
    protected $defer = true;

    public function register()
    {
        static::$initialized = true;
        $this->app['foo'] = 'foo';
    }
}

class ApplicationFactoryProviderStub extends ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->bind('foo', function () {
            static $count = 0;

            return ++$count;
        });
    }
}

class ApplicationMultiProviderStub extends ServiceProvider
{
    protected $defer = true;

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
