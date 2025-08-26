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
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

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
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsForUpdateMigration()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('remove_foo_from_bar', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'bar', false)
            ->andReturn(__DIR__.'/migrations/2025_08_26_110457_remove_foo_from_bar.php');

        $this->runCommand($command, ['name' => 'remove_foo_from_bar']);
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
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'CreateFoo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsForUpdateMigrationWhenNameIsStudlyCase()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('remove_foo_from_bar', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'bar', false)
            ->andReturn(__DIR__.'/migrations/2025_08_26_110457_remove_foo_from_bar.php');

        $this->runCommand($command, ['name' => 'RemoveFooFromBar']);
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
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--create' => 'users']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsForUpdateMigrationWhenTableIsSet()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('remove_foo_from_bar', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'baz', false)
            ->andReturn(__DIR__.'/migrations/2025_08_26_110457_remove_foo_from_bar.php');

        $this->runCommand($command, ['name' => 'remove_foo_from_bar', '--table' => 'baz']);
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
            ->with('create_users_table', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_users_table.php');

        $this->runCommand($command, ['name' => 'create_users_table']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenUpdateTablePatternIsFound()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('remove_foo_from_baz', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'baz', false)
            ->andReturn(__DIR__.'/migrations/2025_08_26_110457_remove_foo_from_baz.php');

        $this->runCommand($command, ['name' => 'remove_foo_from_baz']);
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
            ->with('create_foo', '/home/laravel/vendor/laravel-package/migrations', 'users', true)
            ->andReturn('/home/laravel/vendor/laravel-package/migrations/2021_04_23_110457_create_foo.php');
        $this->runCommand($command, ['name' => 'create_foo', '--path' => 'vendor/laravel-package/migrations', '--create' => 'users']);
    }

    public function testCanSpecifyPathForUpdateMigrationToCreateMigrationsIn()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $command->setLaravel($app);
        $app->setBasePath('/home/laravel');
        $creator->shouldReceive('create')->once()
            ->with('remove_foo_from_bar', '/home/laravel/vendor/laravel-package/migrations', 'bar', false)
            ->andReturn('/home/laravel/vendor/laravel-package/migrations/2025_08_26_110457_remove_foo_from_bar.php');
        $this->runCommand($command, ['name' => 'remove_foo_from_bar', '--path' => 'vendor/laravel-package/migrations']);
    }

    public function testCanSpecifyPathForUpdateMigrationToCreateMigrationsInWhenTableIsSet()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $command->setLaravel($app);
        $app->setBasePath('/home/laravel');
        $creator->shouldReceive('create')->once()
            ->with('remove_foo_from_bar', '/home/laravel/vendor/laravel-package/migrations', 'baz', false)
            ->andReturn('/home/laravel/vendor/laravel-package/migrations/2025_08_26_110457_remove_foo_from_bar.php');
        $this->runCommand($command, ['name' => 'remove_foo_from_bar', '--path' => 'vendor/laravel-package/migrations', '--table' => 'baz']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
