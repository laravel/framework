<?php

namespace Illuminate\Tests\Support;

use Illuminate\Config\Repository as Config;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SupportServiceProviderTest extends TestCase
{
    protected $app;

    protected function setUp(): void
    {
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];

        $this->app = $app = m::mock(Application::class)->makePartial();
        $config = m::mock(Config::class)->makePartial();

        $config = new Config();

        $app->instance('config', $config);
        $config->set('database.migrations.update_date_on_publish', true);

        $one = new ServiceProviderForTestingOne($app);
        $one->boot();
        $two = new ServiceProviderForTestingTwo($app);
        $two->boot();
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testPublishableServiceProviders()
    {
        $toPublish = ServiceProvider::publishableProviders();
        $expected = [
            ServiceProviderForTestingOne::class,
            ServiceProviderForTestingTwo::class,
        ];
        $this->assertEquals($expected, $toPublish, 'Publishable service providers do not return expected set of providers.');
    }

    public function testPublishableGroups()
    {
        $toPublish = ServiceProvider::publishableGroups();
        $this->assertEquals([
            'some_tag',
            'tag_one',
            'tag_two',
            'tag_three',
            'tag_four',
            'tag_five',
        ], $toPublish, 'Publishable groups do not return expected set of groups.');
    }

    public function testSimpleAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish(ServiceProviderForTestingOne::class);
        $this->assertArrayHasKey('source/unmarked/one', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertArrayHasKey('source/tagged/one', $toPublish, 'Service provider does not return expected published path key.');
        $this->assertEquals([
            'source/unmarked/one' => 'destination/unmarked/one',
            'source/tagged/one' => 'destination/tagged/one',
            'source/tagged/multiple' => 'destination/tagged/multiple',
            'source/unmarked/two' => 'destination/unmarked/two',
            'source/tagged/three' => 'destination/tagged/three',
            'source/tagged/multiple_two' => 'destination/tagged/multiple_two',
        ], $toPublish, 'Service provider does not return expected set of published paths.');
    }

    public function testMultipleAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish(ServiceProviderForTestingTwo::class);
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
        $toPublish = ServiceProvider::pathsToPublish(ServiceProviderForTestingOne::class, 'some_tag');
        $this->assertArrayNotHasKey('source/tagged/two/a', $toPublish, 'Service provider does return unexpected tagged path key.');
        $this->assertArrayNotHasKey('source/tagged/two/b', $toPublish, 'Service provider does return unexpected tagged path key.');
        $this->assertArrayHasKey('source/tagged/one', $toPublish, 'Service provider does not return expected tagged path key.');
        $this->assertEquals(['source/tagged/one' => 'destination/tagged/one'], $toPublish, 'Service provider does not return expected set of published tagged paths.');
    }

    public function testMultipleTaggedAssetsArePublishedCorrectly()
    {
        $toPublish = ServiceProvider::pathsToPublish(ServiceProviderForTestingTwo::class, 'some_tag');
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

    public function testPublishesMigrations()
    {
        $serviceProvider = new ServiceProviderForTestingOne($this->app);

        (fn () => $this->publishesMigrations(['source/tagged/four' => 'destination/tagged/four'], 'tag_four'))
            ->call($serviceProvider);

        $this->assertContains('source/tagged/four', ServiceProvider::publishableMigrationPaths());

        $this->app->config->set('database.migrations.update_date_on_publish', false);

        (fn () => $this->publishesMigrations(['source/tagged/five' => 'destination/tagged/five'], 'tag_four'))
            ->call($serviceProvider);

        $this->assertNotContains('source/tagged/five', ServiceProvider::publishableMigrationPaths());

        $this->app->config->set('database.migrations', 'migrations');

        (fn () => $this->publishesMigrations(['source/tagged/five' => 'destination/tagged/five'], 'tag_four'))
            ->call($serviceProvider);

        $this->assertNotContains('source/tagged/five', ServiceProvider::publishableMigrationPaths());

        $this->app->config->set('database.migrations', null);

        (fn () => $this->publishesMigrations(['source/tagged/five' => 'destination/tagged/five'], 'tag_four'))
            ->call($serviceProvider);

        $this->assertNotContains('source/tagged/five', ServiceProvider::publishableMigrationPaths());
    }

    public function testLoadTranslationsFromWithoutNamespace()
    {
        $translator = m::mock(Translator::class);
        $translator->shouldReceive('addPath')->once()->with(__DIR__.'/translations');

        $this->app->shouldReceive('afterResolving')->once()->with('translator', m::on(function ($callback) use ($translator) {
            $callback($translator);

            return true;
        }));

        $provider = new ServiceProviderForTestingOne($this->app);
        $provider->loadTranslationsFrom(__DIR__.'/translations');
    }

    public function testLoadTranslationsFromWithNamespace()
    {
        $translator = m::mock(Translator::class);
        $translator->shouldReceive('addNamespace')->once()->with('namespace', __DIR__.'/translations');

        $this->app->shouldReceive('afterResolving')->once()->with('translator', m::on(function ($callback) use ($translator) {
            $callback($translator);

            return true;
        }));

        $provider = new ServiceProviderForTestingOne($this->app);
        $provider->loadTranslationsFrom(__DIR__.'/translations', 'namespace');
    }
}

class ServiceProviderForTestingOne extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->publishes(['source/unmarked/one' => 'destination/unmarked/one']);
        $this->publishes(['source/tagged/one' => 'destination/tagged/one'], 'some_tag');
        $this->publishes(['source/tagged/multiple' => 'destination/tagged/multiple'], ['tag_one', 'tag_two']);

        $this->publishesMigrations(['source/unmarked/two' => 'destination/unmarked/two']);
        $this->publishesMigrations(['source/tagged/three' => 'destination/tagged/three'], 'tag_three');
        $this->publishesMigrations(['source/tagged/multiple_two' => 'destination/tagged/multiple_two'], ['tag_four', 'tag_five']);
    }

    public function loadTranslationsFrom($path, $namespace = null)
    {
        $this->callAfterResolving('translator', fn ($translator) => is_null($namespace)
            ? $translator->addPath($path)
            : $translator->addNamespace($namespace, $path));
    }
}

class ServiceProviderForTestingTwo extends ServiceProvider
{
    public function register()
    {
        //
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
