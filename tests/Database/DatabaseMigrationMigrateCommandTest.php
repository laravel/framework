<?php

namespace Illuminate\Tests\Database;

use Illuminate\Console\CommandMutex;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Events\SchemaLoaded;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Schema\SchemaState;
use Illuminate\Foundation\Application;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationMigrateCommandTest extends TestCase
{
    public function testBasicMigrationsCallMigratorWithProperArguments()
    {
        $migrator = Mockery::mock(Migrator::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $command = new MigrateCommand($migrator, $dispatcher);
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('hasRunAnyMigrations')->andReturn(true);
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('run')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->expects('repositoryExists')->andReturn(true);

        $this->runCommand($command);
    }

    public function testMigrationsCanBeRunWithStoredSchema()
    {
        $migrator = Mockery::mock(Migrator::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $command = new MigrateCommand($migrator, $dispatcher);
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('hasRunAnyMigrations')->andReturn(false);
        $connection = Mockery::mock(Connection::class);
        $migrator->expects('resolveConnection')->andReturn($connection);
        $connection->expects('getName')->andReturn('mysql');
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('deleteRepository');
        $schemaState = Mockery::mock(SchemaState::class);
        $connection->expects('getSchemaState')->andReturn($schemaState);
        $schemaState->expects('handleOutputUsing')->andReturnSelf();
        $schemaState->expects('load')->with(__DIR__.'/Fixtures/schema.sql');
        $dispatcher->expects('dispatch')->with(Mockery::type(SchemaLoaded::class));
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('run')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->expects('repositoryExists')->andReturn(true);

        $this->runCommand($command, ['--schema-path' => __DIR__.'/Fixtures/schema.sql']);
    }

    public function testMigrationRepositoryCreatedWhenNecessary()
    {
        $migrator = Mockery::mock(Migrator::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $params = [$migrator, $dispatcher];
        $command = $this->getMockBuilder(MigrateCommand::class)->onlyMethods(['callSilent'])->setConstructorArgs($params)->getMock();
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('hasRunAnyMigrations')->andReturn(true);
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('run')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->expects('repositoryExists')->andReturn(false);
        $command->expects($this->once())->method('callSilent')->with('migrate:install', []);

        $this->runCommand($command);
    }

    public function testTheCommandMayBePretended()
    {
        $migrator = Mockery::mock(Migrator::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $command = new MigrateCommand($migrator, $dispatcher);
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('hasRunAnyMigrations')->andReturn(true);
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('run')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => true, 'step' => false]);
        $migrator->expects('repositoryExists')->andReturn(true);

        $this->runCommand($command, ['--pretend' => true]);
    }

    public function testTheDatabaseMayBeSet()
    {
        $migrator = Mockery::mock(Migrator::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $command = new MigrateCommand($migrator, $dispatcher);
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('hasRunAnyMigrations')->andReturn(true);
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('run')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->expects('repositoryExists')->andReturn(true);

        $this->runCommand($command, ['--database' => 'foo']);
    }

    public function testStepMayBeSet()
    {
        $migrator = Mockery::mock(Migrator::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $command = new MigrateCommand($migrator, $dispatcher);
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('hasRunAnyMigrations')->andReturn(true);
        $migrator->expects('usingConnection')->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('run')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => true]);
        $migrator->expects('repositoryExists')->andReturn(true);

        $this->runCommand($command, ['--step' => true]);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseMigrationStub extends Application
{
    public function __construct(array $data = [])
    {
        $mutex = Mockery::mock(CommandMutex::class);
        $mutex->shouldReceive('create')->andReturn(true);
        $mutex->shouldReceive('release')->andReturn(true);
        $this->instance(CommandMutex::class, $mutex);

        foreach ($data as $abstract => $instance) {
            $this->instance($abstract, $instance);
        }
    }

    public function environment(...$environments)
    {
        return 'development';
    }
}
