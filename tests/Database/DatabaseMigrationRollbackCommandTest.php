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
    public function testRollbackCommandCallsMigratorWithProperArguments()
    {
        $migrator = m::mock(Migrator::class);
        $command = new RollbackCommand($migrator);
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('rollback')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => 0, 'batch' => 0]);

        $this->runCommand($command);
    }

    public function testRollbackCommandCallsMigratorWithStepOption()
    {
        $migrator = m::mock(Migrator::class);
        $command = new RollbackCommand($migrator);
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('rollback')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => 2, 'batch' => 0]);

        $this->runCommand($command, ['--step' => 2]);
    }

    public function testRollbackCommandCanBePretended()
    {
        $migrator = m::mock(Migrator::class);
        $command = new RollbackCommand($migrator);
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('rollback')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], true);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo']);
    }

    public function testRollbackCommandCanBePretendedWithStepOption()
    {
        $migrator = m::mock(Migrator::class);
        $command = new RollbackCommand($migrator);
        $app = new ApplicationDatabaseRollbackStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('rollback')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => true, 'step' => 2, 'batch' => 0]);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo', '--step' => 2]);
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
