<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationRollbackCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRollbackCommandCallsMigratorWithProperArguments()
    {
        $command = new RollbackCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('rollback')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => 0, 'path' => null]);

        $this->runCommand($command);
    }

    public function testRollbackCommandCallsMigratorWithStepOption()
    {
        $command = new RollbackCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('rollback')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => 2, 'path' => null]);

        $this->runCommand($command, ['--step' => 2]);
    }

    public function testRollbackCommandCanBePretended()
    {
        $command = new RollbackCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('rollback')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], true);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo']);
    }

    public function testRollbackCommandCanBePretendedWithStepOption()
    {
        $command = new RollbackCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('rollback')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => true, 'step' => 2, 'path' => null]);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo', '--step' => 2]);
    }

    public function testRollbackCommandCallsMigratorWithPathOption()
    {
        $migration = 'database/migrations/2014_10_12_000000_create_users_table.php';
        $command = new RollbackCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('rollback')->once()->with(["/$migration"], ['path' => $migration, 'pretend' => false, 'step' => 0]);

        $this->runCommand($command, ['--path' => $migration]);
    }

    public function testRollbackCommandCallsMigratorWithMultiplePathOption()
    {
        $migrations = [
            'database/migrations/2014_10_12_000000_create_users_table.php',
            'database/migrations/2014_10_12_100000_create_password_resets_table.php',
        ];
        $command = new RollbackCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('rollback')->once()->with(collect($migrations)->map(function ($migration) {
            return '/'.$migration;
        })->toArray(), ['path' => $migrations, 'pretend' => false, 'step' => 0]);

        $this->runCommand($command, ['--path' => $migrations]);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseRollbackStub extends Application
{
    public function __construct(array $data = [])
    {
        foreach ($data as $abstract => $instance) {
            $this->instance($abstract, $instance);
        }
    }

    public function environment(...$environments)
    {
        return 'development';
    }
}
