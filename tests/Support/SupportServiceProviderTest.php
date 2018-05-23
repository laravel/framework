<?php

namespace Illuminate\Tests\Support;

use Mockery as m;
use Illuminate\View\Factory;
use PHPUnit\Framework\TestCase;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Translation\Translator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;

class SupportServiceProviderTest extends TestCase
{
    public function setUp()
    {
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];

        $app = m::mock('Illuminate\\Foundation\\Application')->makePartial();
        $one = new ServiceProviderForTestingOne($app);
        $one->boot();
        $two = new ServiceProviderForTestingTwo($app);
        $two->boot();
    }

    public function tearDown()
    {
        m::close();
    }

    public function testPublishableServiceProviders()
    {
        $toPublish = ServiceProvider::publishableProviders();
        $expected = [
            'Illuminate\Tests\Support\ServiceProviderForTestingOne',
            'Illuminate\Tests\Support\ServiceProviderForTestingTwo',
        ];
        $this->assertEquals($expected, $toPublish, 'Publishable service providers do not return expected set of providers.');
    }

    public function testPublishableGroups()
    {
        $toPublish = ServiceProvider::publishableGroups();
        $this->assertEquals(['some_tag'], $toPublish, 'Publishable groups do not return expected set of groups.');
    }

    public function testSimpleAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish('Illuminate\Tests\Support\ServiceProviderForTestingOne');
        $this->assertArrayHasKey('source/unmarked/one', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/tagged/one', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertEquals(['source/unmarked/one' => 'destination/unmarked/one', 'source/tagged/one' => 'destination/tagged/one'], $toPublish, 'Service provider does not return expected set of published paths.');
    }

    public function testMultipleAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish('Illuminate\Tests\Support\ServiceProviderForTestingTwo');
        $this->assertArrayHasKey('source/unmarked/two/a', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/unmarked/two/b', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/unmarked/two/c', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/tagged/two/a', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/tagged/two/b', $toPublish, 'Service provider does not return expected published path key.');
        $expected = [
            'source/unmarked/two/a' => 'destination/unmarked/two/a',
            'source/unmarked/two/b' => 'destination/unmarked/two/b',
            'source/unmarked/two/c' => 'destination/tagged/two/a',
            'source/tagged/two/a' => 'destination/tagged/two/a',
            'source/tagged/two/b' => 'destination/tagged/two/b',
        ];
        $this->assertEquals($expected, $toPublish, 'Service provider does not return expected set of published paths.');
    }

    public function testSimpleTaggedAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish('Illuminate\Tests\Support\ServiceProviderForTestingOne', 'some_tag');
        $this->assertArrayNotHasKey('source/tagged/two/a', $toPublish, 'Service provider does return unexpected tagged path key.');
        $this->assertArrayNotHasKey('source/tagged/two/b', $toPublish, 'Service provider does return unexpected tagged path key.');
        $this->assertArrayHasKey('source/tagged/one', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertEquals(['source/tagged/one' => 'destination/tagged/one'], $toPublish, 'Service provider does not return expected set of published tagged paths.');
    }

    public function testMultipleTaggedAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish('Illuminate\Tests\Support\ServiceProviderForTestingTwo', 'some_tag');
        $this->assertArrayHasKey('source/tagged/two/a', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertArrayHasKey('source/tagged/two/b', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertArrayNotHasKey('source/tagged/one', $toPublish, 'Service provider does return unexpected tagged path key.');
        $this->assertArrayNotHasKey('source/unmarked/two/c', $toPublish, 'Service provider does return unexpected tagged path key.');
        $expected = [
            'source/tagged/two/a' => 'destination/tagged/two/a',
            'source/tagged/two/b' => 'destination/tagged/two/b',
        ];
        $this->assertEquals($expected, $toPublish, 'Service provider does not return expected set of published tagged paths.');
    }

    public function testMultipleTaggedAssetsAreMergedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish(null, 'some_tag');
        $this->assertArrayHasKey('source/tagged/two/a', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertArrayHasKey('source/tagged/two/b', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertArrayHasKey('source/tagged/one', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertArrayNotHasKey('source/unmarked/two/c', $toPublish, 'Service provider does return unexpected tagged path key.');
        $expected = [
            'source/tagged/one' => 'destination/tagged/one',
            'source/tagged/two/a' => 'destination/tagged/two/a',
            'source/tagged/two/b' => 'destination/tagged/two/b',
        ];
        $this->assertEquals($expected, $toPublish, 'Service provider does not return expected set of published tagged paths.');
    }

    public function testLoadViewsFromPathWhenViewIsNotResolvedYet()
    {
        $view = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->setMethods(['addNamespace'])
            ->getMock();

        $view->expects($this->once())
            ->method('addNamespace')
            ->with('namespace', 'path');

        $config = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $config->expects($this->once())
            ->method('get')
            ->with('view.paths')
            ->willReturn([]);

        $app = $this->getMockBuilder(Application::class)
            ->setMethods(['resolved', 'make', 'afterResolving'])
            ->disableOriginalConstructor()
            ->getMock();

        $app->expects($this->once())
            ->method('resolved')
            ->willReturn(false);

        $app->method('make')
            ->willReturnCallback(function ($abstract) use ($view, $config) {
                $map = compact('view', 'config');

                return $map[$abstract];
            });

        $app->expects($this->once())
            ->method('afterResolving')
            ->with('view', function () {
            })
            ->willReturnCallback(function ($abstract, $callback) use ($view) {
                $callback($view);
            });

        $provider = new class($app) extends ServiceProvider {
            public function register()
            {
                $this->loadViewsFrom('path', 'namespace');
            }
        };

        $provider->register();
    }

    public function testLoadViewsFromPathWhenViewAlreadyResolved()
    {
        $view = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->setMethods(['addNamespace'])
            ->getMock();

        $view->expects($this->once())
            ->method('addNamespace')
            ->with('namespace', 'path');

        $config = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $config->expects($this->once())
            ->method('get')
            ->with('view.paths')
            ->willReturn([]);

        $app = $this->getMockBuilder(Application::class)
            ->setMethods(['resolved', 'make', 'afterResolving'])
            ->disableOriginalConstructor()
            ->getMock();

        $app->expects($this->once())
            ->method('resolved')
            ->willReturn(true);

        $app->expects($this->exactly(2))
            ->method('make')
            ->willReturnCallback(function ($abstract) use ($view, $config) {
                $map = compact('view', 'config');

                return $map[$abstract];
            });

        $app->expects($this->never())->method('afterResolving');

        $provider = new class($app) extends ServiceProvider {
            public function register()
            {
                $this->loadViewsFrom('path', 'namespace');
            }
        };

        $provider->register();
    }

    public function testLoadTranslationsWhenTranlatorNotResolvedYet()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->setMethods(['addNamespace'])
            ->getMock();

        $translator->expects($this->once())
            ->method('addNamespace')
            ->with('namespace', 'path');

        $app = $this->getMockBuilder(Application::class)
            ->setMethods(['resolved', 'afterResolving'])
            ->disableOriginalConstructor()
            ->getMock();

        $app->expects($this->once())
            ->method('resolved')
            ->willReturn(false);

        $app->expects($this->once())
            ->method('afterResolving')
            ->with('translator', function () {
            })
            ->willReturnCallback(function ($abstract, $callback) use ($translator) {
                $callback($translator);
            });

        $provider = new class($app) extends ServiceProvider {
            public function register()
            {
                $this->loadTranslationsFrom('path', 'namespace');
            }
        };

        $provider->register();
    }

    public function testLoadTranslationsWhenTranslatorAlreadyResolved()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->setMethods(['addNamespace'])
            ->getMock();

        $translator->expects($this->once())
            ->method('addNamespace')
            ->with('namespace', 'path');

        $app = $this->getMockBuilder(Application::class)
            ->setMethods(['resolved', 'make', 'afterResolving'])
            ->disableOriginalConstructor()
            ->getMock();

        $app->expects($this->once())
            ->method('resolved')
            ->willReturn(true);

        $app->expects($this->once())
            ->method('make')
            ->with('translator')
            ->willReturn($translator);

        $app->expects($this->never())->method('afterResolving');

        $provider = new class($app) extends ServiceProvider {
            public function register()
            {
                $this->loadTranslationsFrom('path', 'namespace');
            }
        };

        $provider->register();
    }

    public function testLoadJsonTranslationsWhenTranslatorIsNotResolvedYet()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->setMethods(['addJsonPath'])
            ->getMock();

        $translator->expects($this->once())
            ->method('addJsonPath')
            ->with('path');

        $app = $this->getMockBuilder(Application::class)
            ->setMethods(['resolved', 'afterResolving'])
            ->disableOriginalConstructor()
            ->getMock();

        $app->expects($this->once())
            ->method('resolved')
            ->willReturn(false);

        $app->expects($this->once())
            ->method('afterResolving')
            ->with('translator', function () {
            })
            ->willReturnCallback(function ($abstract, $callback) use ($translator) {
                $callback($translator);
            });

        $provider = new class($app) extends ServiceProvider {
            public function register()
            {
                $this->loadJsonTranslationsFrom('path');
            }
        };

        $provider->register();
    }

    public function testLoadJsonTranslationsWhenTralatorAlreadyResolved()
    {
        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->setMethods(['addJsonPath'])
            ->getMock();

        $translator->expects($this->once())
            ->method('addJsonPath')
            ->with('path');

        $app = $this->getMockBuilder(Application::class)
            ->setMethods(['resolved', 'make', 'afterResolving'])
            ->disableOriginalConstructor()
            ->getMock();

        $app->expects($this->once())
            ->method('resolved')
            ->willReturn(true);

        $app->expects($this->once())
            ->method('make')
            ->willReturn($translator);

        $app->expects($this->never())
            ->method('afterResolving');

        $provider = new class($app) extends ServiceProvider {
            public function register()
            {
                $this->loadJsonTranslationsFrom('path');
            }
        };

        $provider->register();
    }

    public function testLoadMigrationsWhenMigratorIsNotResovledYet()
    {
        $migrator = $this->getMockBuilder(Migrator::class)
            ->disableOriginalConstructor()
            ->setMethods(['path'])
            ->getMock();

        $migrator->expects($this->once())
            ->method('path')
            ->with('migrations_path');

        $app = $this->getMockBuilder(Application::class)
            ->setMethods(['resolved', 'afterResolving'])
            ->disableOriginalConstructor()
            ->getMock();

        $app->expects($this->once())
            ->method('resolved')
            ->willReturn(false);

        $app->expects($this->once())
            ->method('afterResolving')
            ->with('migrator', function () {
            })
            ->willReturnCallback(function ($abstract, $callback) use ($migrator) {
                $callback($migrator);
            });

        $provider = new class($app) extends ServiceProvider {
            public function register()
            {
                $this->loadMigrationsFrom('migrations_path');
            }
        };

        $provider->register();
    }

    public function testLoadMigrationsWhenMigratorAlreadyResolved()
    {
        $migrator = $this->getMockBuilder(Migrator::class)
            ->disableOriginalConstructor()
            ->setMethods(['path'])
            ->getMock();

        $migrator->expects($this->once())
            ->method('path')
            ->with('migrations_path');

        $app = $this->getMockBuilder(Application::class)
            ->setMethods(['resolved', 'make', 'afterResolving'])
            ->disableOriginalConstructor()
            ->getMock();

        $app->expects($this->once())
            ->method('resolved')
            ->willReturn(true);

        $app->expects($this->once())
            ->method('make')
            ->with('migrator')
            ->willReturn($migrator);

        $app->expects($this->never())
            ->method('afterResolving');

        $provider = new class($app) extends ServiceProvider {
            public function register()
            {
                $this->loadMigrationsFrom('migrations_path');
            }
        };

        $provider->register();
    }
}

class ServiceProviderForTestingOne extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        $this->publishes(['source/unmarked/one' => 'destination/unmarked/one']);
        $this->publishes(['source/tagged/one' => 'destination/tagged/one'], 'some_tag');
    }
}

class ServiceProviderForTestingTwo extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        $this->publishes(['source/unmarked/two/a' => 'destination/unmarked/two/a']);
        $this->publishes(['source/unmarked/two/b' => 'destination/unmarked/two/b']);
        $this->publishes(['source/unmarked/two/c' => 'destination/tagged/two/a']);
        $this->publishes(['source/tagged/two/a' => 'destination/tagged/two/a'], 'some_tag');
        $this->publishes(['source/tagged/two/b' => 'destination/tagged/two/b'], 'some_tag');
    }
}
