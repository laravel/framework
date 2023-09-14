<?php

namespace Illuminate\Tests\Database;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Console\Generators\PresetManager;
use Illuminate\Console\Generators\Presets\Laravel;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Foundation\Application;
use Illuminate\Support\Composer;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationMakeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicCreateDumpsAutoload()
    {
        $preset = m::mock(PresetManager::class);
        $laravel = m::mock(Laravel::class);
        $preset->shouldReceive('driver')->andReturn($laravel);
        $laravel->shouldReceive('is')->with('laravel')->andReturnTrue();

        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            $composer = m::mock(Composer::class)
        );
        $app = new Application;
        $app->instance(PresetManager::class, $preset);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
                ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)
                ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArguments()
    {
        $preset = m::mock(PresetManager::class);
        $laravel = m::mock(Laravel::class);
        $preset->shouldReceive('driver')->andReturn($laravel);
        $laravel->shouldReceive('is')->with('laravel')->andReturnTrue();

        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->instance(PresetManager::class, $preset);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
                ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)
                ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenNameIsStudlyCase()
    {
        $preset = m::mock(PresetManager::class);
        $laravel = m::mock(Laravel::class);
        $preset->shouldReceive('driver')->andReturn($laravel);
        $laravel->shouldReceive('is')->with('laravel')->andReturnTrue();

        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->instance(PresetManager::class, $preset);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
                ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)
                ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'CreateFoo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
    {
        $preset = m::mock(PresetManager::class);
        $laravel = m::mock(Laravel::class);
        $preset->shouldReceive('driver')->andReturn($laravel);
        $laravel->shouldReceive('is')->with('laravel')->andReturnTrue();

        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->instance(PresetManager::class, $preset);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
                ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true)
                ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--create' => 'users']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenCreateTablePatternIsFound()
    {
        $preset = m::mock(PresetManager::class);
        $laravel = m::mock(Laravel::class);
        $preset->shouldReceive('driver')->andReturn($laravel);
        $laravel->shouldReceive('is')->with('laravel')->andReturnTrue();

        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->instance(PresetManager::class, $preset);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
                ->with('create_users_table', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true)
                ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_users_table.php');

        $this->runCommand($command, ['name' => 'create_users_table']);
    }

    public function testCanSpecifyPathToCreateMigrationsIn()
    {
        $preset = m::mock(PresetManager::class);
        $laravel = m::mock(Laravel::class);
        $preset->shouldReceive('driver')->andReturn($laravel);
        $laravel->shouldReceive('is')->with('laravel')->andReturnTrue();

        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->instance(PresetManager::class, $preset);
        $command->setLaravel($app);
        $app->setBasePath('/home/laravel');
        $creator->shouldReceive('create')->once()
                ->with('create_foo', '/home/laravel/vendor/laravel-package/migrations', 'users', true)
                ->andReturn('/home/laravel/vendor/laravel-package/migrations/2021_04_23_110457_create_foo.php');
        $this->runCommand($command, ['name' => 'create_foo', '--path' => 'vendor/laravel-package/migrations', '--create' => 'users']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
