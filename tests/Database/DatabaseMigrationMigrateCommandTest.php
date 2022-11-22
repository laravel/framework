<?php

namespace Illuminate\Tests\Database;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\CommandMutex;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\LoadCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Events\SchemaLoaded;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationMigrateCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicMigrationsCallMigratorWithProperArguments()
    {
        $command = new MigrateCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('hasRunAnyMigrations')->andReturn(true);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->shouldReceive('getNotes')->andReturn([]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command);
    }

    public function testMigrationsCanBeRunWithStoredSchema()
    {
        $command = new MigrateCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $params = [
            $resolver = m::mock(ConnectionResolverInterface::class),
            $dispatcher = m::mock(Dispatcher::class),
        ];
        $loadCommand = $this->getMockBuilder(LoadCommand::class)->onlyMethods(['callSilent'])->setConstructorArgs($params)->getMock();
        $loadCommand->setLaravel($app);
        $loadCommand->setApplication($console);
        $loadCommand->ignoreValidationErrors();

        $container = Container::setInstance(new Container);

        $container->singleton('config', function () {
            return new Config(['database.migrations' => 'migrations']);
        });

        $schemaPath = __DIR__.'/stubs/schema.sql';

        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
        $migrator->shouldReceive('hasRunAnyMigrations')->andReturn(false);
        $console->shouldReceive('find')->with('schema:load')->andReturn($loadCommand);
        $resolver->shouldReceive('connection')->andReturn($connection = m::mock(Connection::class));
        $connection->shouldReceive('getName')->andReturn('mysql');
        $connection->shouldReceive('getSchemaBuilder')->andReturn($schemaBuilder = m::mock(stdClass::class));
        $schemaBuilder->shouldReceive('hasTable')->with('migrations')->andReturn(true);
        $connection->shouldReceive('getSchemaState')->andReturn($schemaState = m::mock(stdClass::class));
        $schemaState->shouldReceive('handleOutputUsing')->andReturnSelf();
        $schemaState->shouldReceive('load')->once()->with($schemaPath);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(SchemaLoaded::class));
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);

        $loadCommand->expects($this->once())->method('callSilent')->with($this->equalTo('db:wipe'), $this->equalTo(['--force' => true]));

        $this->runCommand($command, ['--schema-path' => $schemaPath]);
    }

    public function testMigrationRepositoryCreatedWhenNecessary()
    {
        $params = [$migrator = m::mock(Migrator::class)];
        $command = $this->getMockBuilder(MigrateCommand::class)->onlyMethods(['callSilent'])->setConstructorArgs($params)->getMock();
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('hasRunAnyMigrations')->andReturn(true);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(false);
        $command->expects($this->once())->method('callSilent')->with($this->equalTo('migrate:install'), $this->equalTo([]));

        $this->runCommand($command);
    }

    public function testTheCommandMayBePretended()
    {
        $command = new MigrateCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('hasRunAnyMigrations')->andReturn(true);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => true, 'step' => false]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--pretend' => true]);
    }

    public function testTheDatabaseMayBeSet()
    {
        $command = new MigrateCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('hasRunAnyMigrations')->andReturn(true);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--database' => 'foo']);
    }

    public function testStepMayBeSet()
    {
        $command = new MigrateCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('hasRunAnyMigrations')->andReturn(true);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => true]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

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
        $mutex = m::mock(CommandMutex::class);
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
