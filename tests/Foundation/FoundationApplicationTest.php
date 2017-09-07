<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;

class FoundationApplicationTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testSetLocaleSetsLocaleAndFiresLocaleChangedEvent()
    {
        $app = new Application;
        $app['config'] = $config = m::mock('stdClass');
        $config->shouldReceive('set')->once()->with('app.locale', 'foo');
        $app['translator'] = $trans = m::mock('stdClass');
        $trans->shouldReceive('setLocale')->once()->with('foo');
        $app['events'] = $events = m::mock('stdClass');
        $events->shouldReceive('dispatch')->once()->with(m::type('Illuminate\Foundation\Events\LocaleUpdated'));

        $app->setLocale('foo');
    }

    public function testServiceProvidersAreCorrectlyRegistered()
    {
        $provider = m::mock('Illuminate\Tests\Foundation\ApplicationBasicServiceProviderStub');
        $class = get_class($provider);
        $provider->shouldReceive('register')->once();
        $app = new Application;
        $app->register($provider);

        $this->assertTrue(in_array($class, $app->getLoadedProviders()));
    }

    public function testServiceProvidersAreCorrectlyRegisteredWhenRegisterMethodIsNotPresent()
    {
        $provider = m::mock('Illuminate\Support\ServiceProvider');
        $class = get_class($provider);
        $provider->shouldReceive('register')->never();
        $app = new Application;
        $app->register($provider);

        $this->assertTrue(in_array($class, $app->getLoadedProviders()));
    }

    public function testDeferredServicesMarkedAsBound()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => 'Illuminate\Tests\Foundation\ApplicationDeferredServiceProviderStub']);
        $this->assertTrue($app->bound('foo'));
        $this->assertEquals('foo', $app->make('foo'));
    }

    public function testDeferredServicesAreSharedProperly()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => 'Illuminate\Tests\Foundation\ApplicationDeferredSharedServiceProviderStub']);
        $this->assertTrue($app->bound('foo'));
        $one = $app->make('foo');
        $two = $app->make('foo');
        $this->assertInstanceOf('stdClass', $one);
        $this->assertInstanceOf('stdClass', $two);
        $this->assertSame($one, $two);
    }

    public function testDeferredServicesCanBeExtended()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => 'Illuminate\Tests\Foundation\ApplicationDeferredServiceProviderStub']);
        $app->extend('foo', function ($instance, $container) {
            return $instance.'bar';
        });
        $this->assertEquals('foobar', $app->make('foo'));
    }

    public function testDeferredServiceProviderIsRegisteredOnlyOnce()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => 'Illuminate\Tests\Foundation\ApplicationDeferredServiceProviderCountStub']);
        $obj = $app->make('foo');
        $this->assertInstanceOf('stdClass', $obj);
        $this->assertSame($obj, $app->make('foo'));
        $this->assertEquals(1, ApplicationDeferredServiceProviderCountStub::$count);
    }

    public function testDeferredServiceDontRunWhenInstanceSet()
    {
        $app = new Application;
        $app->setDeferredServices(['foo' => 'Illuminate\Tests\Foundation\ApplicationDeferredServiceProviderStub']);
        $app->instance('foo', 'bar');
        $instance = $app->make('foo');
        $this->assertEquals($instance, 'bar');
    }

    public function testDeferredServicesAreLazilyInitialized()
    {
        ApplicationDeferredServiceProviderStub::$initialized = false;
        $app = new Application;
        $app->setDeferredServices(['foo' => 'Illuminate\Tests\Foundation\ApplicationDeferredServiceProviderStub']);
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
        $app->setDeferredServices(['foo' => 'Illuminate\Tests\Foundation\ApplicationFactoryProviderStub']);
        $this->assertTrue($app->bound('foo'));
        $this->assertEquals(1, $app->make('foo'));
        $this->assertEquals(2, $app->make('foo'));
        $this->assertEquals(3, $app->make('foo'));
    }

    public function testSingleProviderCanProvideMultipleDeferredServices()
    {
        $app = new Application;
        $app->setDeferredServices([
            'foo' => 'Illuminate\Tests\Foundation\ApplicationMultiProviderStub',
            'bar' => 'Illuminate\Tests\Foundation\ApplicationMultiProviderStub',
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
        };
        $app->afterLoadingEnvironment($closure);
        $this->assertArrayHasKey(0, $app['events']->getListeners('bootstrapped: Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables'));
        // $this->assertSame($closure, $app['events']->getListeners('bootstrapped: Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables')[0]);
    }

    public function testBeforeBootstrappingAddsClosure()
    {
        $app = new Application;
        $closure = function () {
        };
        $app->beforeBootstrapping('Illuminate\Foundation\Bootstrap\RegisterFacades', $closure);
        $this->assertArrayHasKey(0, $app['events']->getListeners('bootstrapping: Illuminate\Foundation\Bootstrap\RegisterFacades'));
        // $this->assertSame($closure, $app['events']->getListeners('bootstrapping: Illuminate\Foundation\Bootstrap\RegisterFacades')[0]);
    }

    public function testAfterBootstrappingAddsClosure()
    {
        $app = new Application;
        $closure = function () {
        };
        $app->afterBootstrapping('Illuminate\Foundation\Bootstrap\RegisterFacades', $closure);
        $this->assertArrayHasKey(0, $app['events']->getListeners('bootstrapped: Illuminate\Foundation\Bootstrap\RegisterFacades'));
        // $this->assertSame($closure, $app['events']->getListeners('bootstrapped: Illuminate\Foundation\Bootstrap\RegisterFacades')[0]);
    }
}

class ApplicationBasicServiceProviderStub extends \Illuminate\Support\ServiceProvider
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

class ApplicationDeferredSharedServiceProviderStub extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton('foo', function () {
            return new \stdClass;
        });
    }
}

class ApplicationDeferredServiceProviderCountStub extends \Illuminate\Support\ServiceProvider
{
    public static $count = 0;
    protected $defer = true;

    public function register()
    {
        static::$count++;
        $this->app['foo'] = new \stdClass;
    }
}

class ApplicationDeferredServiceProviderStub extends \Illuminate\Support\ServiceProvider
{
    public static $initialized = false;
    protected $defer = true;

    public function register()
    {
        static::$initialized = true;
        $this->app['foo'] = 'foo';
    }
}

class ApplicationFactoryProviderStub extends \Illuminate\Support\ServiceProvider
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

class ApplicationMultiProviderStub extends \Illuminate\Support\ServiceProvider
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
