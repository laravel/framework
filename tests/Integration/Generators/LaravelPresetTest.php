<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Console\Generators\PresetManager;
use Illuminate\Console\Generators\Presets\Laravel;

class LaravelPresetTest extends TestCase
{
    public function testItCanBeResolved()
    {
        $preset = $this->app[PresetManager::class]->driver('laravel');

        $this->assertInstanceOf(Laravel::class, $preset);
        $this->assertSame('laravel', $preset->name());
        $this->assertTrue($preset->is('laravel'));

        $this->assertSame($this->app->basePath(), $preset->basePath());
        $this->assertSame($this->app->basePath('app'), $preset->sourcePath());
        $this->assertSame($this->app->basePath('tests'), $preset->testingPath());
        $this->assertSame($this->app->resourcePath(), $preset->resourcePath());
        $this->assertSame($this->app->resourcePath('views'), $preset->viewPath());
        $this->assertSame($this->app->databasePath('factories'), $preset->factoryPath());
        $this->assertSame($this->app->databasePath('migrations'), $preset->migrationPath());
        $this->assertSame($this->app->databasePath('seeders'), $preset->seederPath());

        $this->assertSame($this->app->getNamespace(), $preset->rootNamespace());
        $this->assertSame($this->app->getNamespace().'Console\Commands\\', $preset->commandNamespace());
        $this->assertSame($this->app->getNamespace().'Models\\', $preset->modelNamespace());
        $this->assertSame($this->app->getNamespace().'Providers\\', $preset->providerNamespace());
        $this->assertSame('Database\Factories\\', $preset->factoryNamespace());
        $this->assertSame('Database\Seeders\\', $preset->seederNamespace());
        $this->assertSame('Tests\\', $preset->testingNamespace());

        $this->assertTrue($preset->hasCustomStubPath());
        $this->assertSame('Illuminate\Foundation\Auth\User', $preset->userProviderModel());
    }

    public function testItAvailableAsTheDefaultDriver()
    {
        $preset = $this->app[PresetManager::class]->driver();

        $this->assertInstanceOf(Laravel::class, $preset);
        $this->assertSame('laravel', $preset->name());
        $this->assertTrue($preset->is('laravel'));
    }
}
