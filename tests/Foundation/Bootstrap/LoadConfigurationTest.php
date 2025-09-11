<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LoadConfigurationTest extends TestCase
{
    public function testLoadsBaseConfiguration()
    {
        $app = new Application();

        (new LoadConfiguration)->bootstrap($app);

        $this->assertSame('Laravel', $app['config']['app.name']);
    }

    public function testSetsEnvironmentResolver()
    {
        $app = new Application();
        $this->assertNull((new ReflectionClass($app))->getProperty('environmentResolver')->getValue($app));

        (new LoadConfiguration)->bootstrap($app);

        $this->assertInstanceOf(
            Closure::class,
            (new ReflectionClass($app))->getProperty('environmentResolver')->getValue($app)
        );
    }

    public function testDontLoadBaseConfiguration()
    {
        $app = new Application();
        $app->dontMergeFrameworkConfiguration();

        (new LoadConfiguration)->bootstrap($app);

        $this->assertNull($app['config']['app.name']);
    }

    public function testLoadsConfigurationInIsolation()
    {
        $app = new Application(__DIR__.'/../fixtures');
        $app->useConfigPath(__DIR__.'/../fixtures/config');

        (new LoadConfiguration)->bootstrap($app);

        $this->assertNull($app['config']['bar.foo']);
        $this->assertSame('bar', $app['config']['custom.foo']);
    }

    public function testConfigurationArrayKeysMatchLoadedFilenames()
    {
        $baseConfigPath = __DIR__.'/../../../config';
        $customConfigPath = __DIR__.'/../fixtures/config';

        $app = new Application();
        $app->useConfigPath($customConfigPath);

        (new LoadConfiguration)->bootstrap($app);

        $this->assertEqualsCanonicalizing(
            array_keys($app['config']->all()),
            collect((new Filesystem)->files([
                $baseConfigPath,
                $customConfigPath,
            ]))->map(fn ($file) => $file->getBaseName('.php'))->unique()->values()->toArray()
        );
    }
}
