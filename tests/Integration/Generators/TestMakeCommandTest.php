<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Console\Generators\PresetManager;
use Illuminate\Console\Generators\Presets\Laravel;

class TestMakeCommandTest extends TestCase
{
    protected $files = [
        'tests/Feature/FooTest.php',
        'tests/Unit/FooTest.php',
    ];

    public function testItCanGenerateFeatureTest()
    {
        $this->artisan('make:test', ['name' => 'FooTest'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Tests\Feature;',
            'use Illuminate\Foundation\Testing\RefreshDatabase;',
            'use Illuminate\Foundation\Testing\WithFaker;',
            'use Tests\TestCase;',
            'class FooTest extends TestCase',
        ], 'tests/Feature/FooTest.php');
    }

    public function testItCanGenerateUnitTest()
    {
        $this->artisan('make:test', ['name' => 'FooTest', '--unit' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Tests\Unit;',
            'use PHPUnit\Framework\TestCase;',
            'class FooTest extends TestCase',
        ], 'tests/Unit/FooTest.php');
    }

    public function testItCanGenerateFeatureTestWithCustomNamespace()
    {
        $manager = $this->app->make(PresetManager::class);

        $manager->extend('acme', fn () => new class($this->app) extends Laravel
        {
            public function testingNamespace()
            {
                return 'Acme\Tests';
            }
        });

        $this->artisan('make:test', ['name' => 'FooTest', '--preset' => 'acme'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Acme\Tests\Feature;',
            'use Illuminate\Foundation\Testing\RefreshDatabase;',
            'use Illuminate\Foundation\Testing\WithFaker;',
            'use Tests\TestCase;',
            'class FooTest extends TestCase',
        ], 'tests/Feature/FooTest.php');
    }

    public function testItCanGenerateUnitTestWithCustomNamespace()
    {
        $manager = $this->app->make(PresetManager::class);

        $manager->extend('acme', fn () => new class($this->app) extends Laravel
        {
            public function testingNamespace()
            {
                return 'Acme\Tests';
            }
        });

        $this->artisan('make:test', ['name' => 'FooTest', '--unit' => true, '--preset' => 'acme'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Acme\Tests\Unit;',
            'use PHPUnit\Framework\TestCase;',
            'class FooTest extends TestCase',
        ], 'tests/Unit/FooTest.php');
    }

    public function testItCanGenerateFeatureTestUsingPest()
    {
        $this->artisan('make:test', ['name' => 'FooTest', '--pest' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'test(\'example\', function () {',
            '$response = $this->get(\'/\');',
            '$response->assertStatus(200);',
        ], 'tests/Feature/FooTest.php');
    }

    public function testItCanGenerateUnitTestUsingPest()
    {
        $this->artisan('make:test', ['name' => 'FooTest', '--unit' => true, '--pest' => true])
            ->assertExitCode(0);
        $this->assertFileContains([
            'test(\'example\', function () {',
            'expect(true)->toBeTrue();',
        ], 'tests/Unit/FooTest.php');
    }

    public function testItCanGenerateFeatureTestUsingPestWithCustomNamespace()
    {
        $manager = $this->app->make(PresetManager::class);

        $manager->extend('acme', fn () => new class($this->app) extends Laravel
        {
            public function testingNamespace()
            {
                return 'Acme\Tests';
            }
        });

        $this->artisan('make:test', ['name' => 'FooTest', '--pest' => true, '--preset' => 'acme'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'test(\'example\', function () {',
            '$response = $this->get(\'/\');',
            '$response->assertStatus(200);',
        ], 'tests/Feature/FooTest.php');
    }

    public function testItCanGenerateUnitTestUsingPestWithCustomNamespace()
    {
        $manager = $this->app->make(PresetManager::class);

        $manager->extend('acme', fn () => new class($this->app) extends Laravel
        {
            public function testingNamespace()
            {
                return 'Acme\Tests';
            }
        });

        $this->artisan('make:test', ['name' => 'FooTest', '--unit' => true, '--pest' => true, '--preset' => 'acme'])
            ->assertExitCode(0);
        $this->assertFileContains([
            'test(\'example\', function () {',
            'expect(true)->toBeTrue();',
        ], 'tests/Unit/FooTest.php');
    }
}
