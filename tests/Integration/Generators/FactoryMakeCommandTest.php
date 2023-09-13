<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Console\Generators\PresetManager;
use Illuminate\Console\Generators\Presets\Laravel;

class FactoryMakeCommandTest extends TestCase
{
    protected $files = [
        'database/factories/FooFactory.php',
    ];

    /** @test */
    public function testItCanGenerateFactoryFile()
    {
        $this->artisan('make:factory', ['name' => 'FooFactory'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Database\Factories;',
            'use Illuminate\Database\Eloquent\Factories\Factory;',
            'class FooFactory extends Factory',
            'public function definition()',
        ], 'database/factories/FooFactory.php');
    }

    public function testItCanGenerateFactoryFileWithCustomPreset()
    {
        $manager = $this->app->make(PresetManager::class);

        $manager->extend('acme', fn () => new class('App', $this->app->basePath(), $this->app->make('config')) extends Laravel
        {
            public function factoryNamespace()
            {
                return 'Acme\Database\Factory';
            }
        });

        $this->artisan('make:factory', ['name' => 'FooFactory', '--preset' => 'acme'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Acme\Database\Factory;',
            'use Illuminate\Database\Eloquent\Factories\Factory;',
            'class FooFactory extends Factory',
            'public function definition()',
        ], 'database/factories/FooFactory.php');
    }
}
