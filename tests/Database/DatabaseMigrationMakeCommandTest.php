<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;

class DatabaseMigrationMakeCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicCreateDumpsAutoload()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'),
            $composer = m::mock('Illuminate\Support\Composer')
        );
        $app = new \Illuminate\Foundation\Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', null, false);
        $composer->shouldReceive('dumpAutoloads')->once();

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArguments()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'),
            m::mock('Illuminate\Support\Composer')->shouldIgnoreMissing()
        );
        $app = new \Illuminate\Foundation\Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', null, false);

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenNameIsStudlyCase()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'),
            m::mock('Illuminate\Support\Composer')->shouldIgnoreMissing()
        );
        $app = new \Illuminate\Foundation\Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', null, false);

        $this->runCommand($command, ['name' => 'CreateFoo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'),
            m::mock('Illuminate\Support\Composer')->shouldIgnoreMissing()
        );
        $app = new \Illuminate\Foundation\Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true);

        $this->runCommand($command, ['name' => 'create_foo', '--create' => 'users']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenCreateTablePatternIsFound()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'),
            m::mock('Illuminate\Support\Composer')->shouldIgnoreMissing()
        );
        $app = new \Illuminate\Foundation\Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()->with('create_users_table', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true);

        $this->runCommand($command, ['name' => 'create_users_table']);
    }

    public function testCanSpecifyPathToCreateMigrationsIn()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'),
            m::mock('Illuminate\Support\Composer')->shouldIgnoreMissing()
        );
        $app = new \Illuminate\Foundation\Application;
        $command->setLaravel($app);
        $app->setBasePath('/home/laravel');
        $creator->shouldReceive('create')->once()->with('create_foo', '/home/laravel/vendor/laravel-package/migrations', 'users', true);
        $this->runCommand($command, ['name' => 'create_foo', '--path' => 'vendor/laravel-package/migrations', '--create' => 'users']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new \Symfony\Component\Console\Input\ArrayInput($input), new \Symfony\Component\Console\Output\NullOutput);
    }
}
