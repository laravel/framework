<?php

namespace Illuminate\Tests\Database;

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
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            $composer = m::mock(Composer::class)
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__ . DIRECTORY_SEPARATOR . 'migrations', 'foo', true, 'default')
            ->andReturn(__DIR__ . '/migrations/2021_04_23_110457_create_foo.php');
        $composer->shouldReceive('dumpAutoloads')->once();

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArguments()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__ . DIRECTORY_SEPARATOR . 'migrations', 'foo', true, 'default')
            ->andReturn(__DIR__ . '/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenNameIsStudlyCase()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__ . DIRECTORY_SEPARATOR . 'migrations', 'foo', true, 'default')
            ->andReturn(__DIR__ . '/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'CreateFoo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__ . DIRECTORY_SEPARATOR . 'migrations', 'users', true, 'default')
            ->andReturn(__DIR__ . '/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--create' => 'users']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenCreateTablePatternIsFound()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_users_table', __DIR__ . DIRECTORY_SEPARATOR . 'migrations', 'users', true, 'default')
            ->andReturn(__DIR__ . '/migrations/2021_04_23_110457_create_users_table.php');

        $this->runCommand($command, ['name' => 'create_users_table']);
    }

    public function testCanSpecifyPathToCreateMigrationsIn()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $command->setLaravel($app);
        $app->setBasePath('/home/laravel');
        $creator->shouldReceive('create')->once()
            ->with('create_foo', '/home/laravel/vendor/laravel-package/migrations', 'users', true, 'default')
            ->andReturn('/home/laravel/vendor/laravel-package/migrations/2021_04_23_110457_create_foo.php');
        $this->runCommand($command,
            ['name' => 'create_foo', '--path' => 'vendor/laravel-package/migrations', '--create' => 'users']);
    }

    public function testCanSpecifyEmptyPrimaryOptionToCreateMigrations()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__ . DIRECTORY_SEPARATOR . 'migrations', 'foo', true, 'default')
            ->andReturn(__DIR__ . '/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--primary' => null]);
    }

    public function testCanSpecifyDefaultPrimaryOptionToCreateMigrations()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__ . DIRECTORY_SEPARATOR . 'migrations', 'foo', true, 'default')
            ->andReturn(__DIR__ . '/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--primary' => 'default']);
    }

    public function testCanSpecifyUuidPrimaryOptionToCreateMigrations()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__ . DIRECTORY_SEPARATOR . 'migrations', 'foo', true, 'uuid')
            ->andReturn(__DIR__ . '/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--primary' => 'uuid']);
    }

    public function testCanSpecifyUlidPrimaryOptionToCreateMigrations()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__ . DIRECTORY_SEPARATOR . 'migrations', 'foo', true, 'ulid')
            ->andReturn(__DIR__ . '/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--primary' => 'ulid']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
