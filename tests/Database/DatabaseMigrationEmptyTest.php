<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Database\Console\Migrations\EmptyCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\TableDroppers\Sqlite;
use Illuminate\Database\Console\Migrations\TableDroppers\Mysql;
use Illuminate\Database\Console\Migrations\TableDroppers\Pgsql;
use Symfony\Component\Console\Application as ConsoleApplication;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Console\Migrations\TableDroppers\Sqlserver;

class DatabaseMigrationEmptyTest extends TestCase
{
    protected $connection;

    protected $output;

    protected $resolver;

    protected $container;

    protected $command;

    protected function setUp()
    {
        $this->output = new BufferedOutput;
        $this->connection = m::mock(Connection::class);
        $this->resolver = m::mock(Resolver::class);
        $this->container = m::mock(Container::class);
        $this->command = new EmptyCommand($this->resolver);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testEmptyCommandShowsErrorWhenDatabaseDriverNotSupported()
    {
        $this->connection->shouldReceive('getDriverName')->twice()->andReturn('other');
        
        $this->resolver->shouldReceive('setDefaultConnection')->once()->with('other');
        $this->resolver->shouldReceive('connection')->twice()->andReturn($this->connection);
        
        $this->container->shouldReceive('call');
        $this->container->shouldReceive('environment')->once()->andReturn('testing');
        $this->container->shouldReceive('make')->never();
       
        $this->command->setLaravel($this->container);

        $this->runCommand(['--database' => 'other']);
       
        $this->assertStringStartsWith('Sorry - the "db:empty" command does not support "other" database drivers at this stage', $this->output->fetch());
    }

    public function testEmptyCommandCallsCommandsWithProperArgumentsForSqlite()
    {
        $this->connection->shouldReceive('getDriverName')->once()->andReturn('sqlite');

        $sqlite = m::mock(Sqlite::class);
        $sqlite->shouldReceive('dropAllTables')->once()->with($this->connection);

        $this->resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');
        $this->resolver->shouldReceive('connection')->twice()->andReturn($this->connection);

        $this->container->shouldReceive('call');
        $this->container->shouldReceive('environment')->once()->andReturn('testing');
        $this->container->shouldReceive('make')->once()->with('\Illuminate\Database\Console\Migrations\TableDroppers\Sqlite')->andReturn($sqlite);

        $this->command->setLaravel($this->container);

        $this->runCommand(['--database' => 'sqlite']);

        $this->assertStringStartsWith('Dropping all tables...', $this->output->fetch());
    }

    public function testEmptyCommandCallsCommandsWithProperArgumentsForMysql()
    {
        $this->connection->shouldReceive('getDriverName')->once()->andReturn('mysql');

        $mysql = m::mock(Mysql::class);
        $mysql->shouldReceive('dropAllTables')->once()->with($this->connection);

        $this->resolver->shouldReceive('setDefaultConnection')->once()->with('mysql');
        $this->resolver->shouldReceive('connection')->twice()->andReturn($this->connection);

        $this->container->shouldReceive('call');
        $this->container->shouldReceive('environment')->once()->andReturn('testing');
        $this->container->shouldReceive('make')->once()->with('\Illuminate\Database\Console\Migrations\TableDroppers\Mysql')->andReturn($mysql);

        $this->command->setLaravel($this->container);

        $this->runCommand(['--database' => 'mysql']);

        $this->assertStringStartsWith('Dropping all tables...', $this->output->fetch());
    }

    public function testEmptyCommandCallsCommandsWithProperArgumentsForPgsql()
    {
        $this->connection->shouldReceive('getDriverName')->once()->andReturn('pgsql');

        $pgsql = m::mock(Pgsql::class);
        $pgsql->shouldReceive('dropAllTables')->once()->with($this->connection);

        $this->resolver->shouldReceive('setDefaultConnection')->once()->with('pgsql');
        $this->resolver->shouldReceive('connection')->twice()->andReturn($this->connection);

        $this->container->shouldReceive('call');
        $this->container->shouldReceive('environment')->once()->andReturn('testing');
        $this->container->shouldReceive('make')->once()->with('\Illuminate\Database\Console\Migrations\TableDroppers\Pgsql')->andReturn($pgsql);

        $this->command->setLaravel($this->container);

        $this->runCommand(['--database' => 'pgsql']);

        $this->assertStringStartsWith('Dropping all tables...', $this->output->fetch());
    }

    public function testEmptyCommandCallsCommandsWithProperArgumentsForSqlserver()
    {
        $this->connection->shouldReceive('getDriverName')->once()->andReturn('sqlserver');

        $sqlserver = m::mock(Sqlserver::class);
        $sqlserver->shouldReceive('dropAllTables')->once()->with($this->connection);

        $this->resolver->shouldReceive('setDefaultConnection')->once()->with('sqlserver');
        $this->resolver->shouldReceive('connection')->twice()->andReturn($this->connection);

        $this->container->shouldReceive('call');
        $this->container->shouldReceive('environment')->once()->andReturn('testing');
        $this->container->shouldReceive('make')->once()->with('\Illuminate\Database\Console\Migrations\TableDroppers\Sqlserver')->andReturn($sqlserver);

        $this->command->setLaravel($this->container);

        $this->runCommand(['--database' => 'sqlserver']);

        $this->assertStringStartsWith('Dropping all tables...', $this->output->fetch());
    }

    public function testEmptyCommandCallsCommandsWithProperArgumentsForMigrationsAndSeeding()
    {
        $this->connection->shouldReceive('getDriverName')->once()->andReturn('sqlite');

        $sqlite = m::mock(Sqlite::class);
        $sqlite->shouldReceive('dropAllTables')->once()->with($this->connection);

        $this->resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');
        $this->resolver->shouldReceive('connection')->twice()->andReturn($this->connection);

        $migrateCommand = m::mock(MigrateCommand::class);
        $seedCommand = m::mock(SeedCommand::class);

        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $this->command->setApplication($console);

        $console->shouldReceive('find')->with('migrate')->andReturn($migrateCommand);
        $console->shouldReceive('find')->with('db:seed')->andReturn($seedCommand);

        $migrateCommand->shouldReceive('run')->once();
        $seedCommand->shouldReceive('run')->once();

        $this->container->shouldReceive('call');
        $this->container->shouldReceive('environment')->once()->andReturn('testing');
        $this->container->shouldReceive('make')->once()->with('\Illuminate\Database\Console\Migrations\TableDroppers\Sqlite')->andReturn($sqlite);

        $this->command->setLaravel($this->container);

        $this->runCommand(['--database' => 'sqlite', '--migrate' => true, '--seed' => true]);

        $this->assertStringStartsWith("Dropping all tables...\nRunning migrations...\nRunning seeders...", $this->output->fetch());
    }

    public function testEmptyCommandCallsCommandsWithProperArgumentsForOnlyMigrations()
    {
        $this->connection->shouldReceive('getDriverName')->once()->andReturn('sqlite');

        $sqlite = m::mock(Sqlite::class);
        $sqlite->shouldReceive('dropAllTables')->once()->with($this->connection);

        $this->resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');
        $this->resolver->shouldReceive('connection')->twice()->andReturn($this->connection);

        $migrateCommand = m::mock(MigrateCommand::class);
        $seedCommand = m::mock(SeedCommand::class);

        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $this->command->setApplication($console);

        $console->shouldReceive('find')->with('migrate')->andReturn($migrateCommand);
        $console->shouldReceive('find')->with('db:seed')->andReturn($seedCommand);

        $migrateCommand->shouldReceive('run')->once();
        $seedCommand->shouldReceive('run')->never();

        $this->container->shouldReceive('call');
        $this->container->shouldReceive('environment')->once()->andReturn('testing');
        $this->container->shouldReceive('make')->once()->with('\Illuminate\Database\Console\Migrations\TableDroppers\Sqlite')->andReturn($sqlite);

        $this->command->setLaravel($this->container);

        $this->runCommand(['--database' => 'sqlite', '--migrate' => true, '--seed' => false]);

        $this->assertStringStartsWith("Dropping all tables...\nRunning migrations...", $this->output->fetch());
    }

    public function testEmptyCommandCallsCommandsWithProperArgumentsForOnlySeeder()
    {
        $this->connection->shouldReceive('getDriverName')->once()->andReturn('sqlite');

        $sqlite = m::mock(Sqlite::class);
        $sqlite->shouldReceive('dropAllTables')->once()->with($this->connection);

        $this->resolver->shouldReceive('setDefaultConnection')->once()->with('sqlite');
        $this->resolver->shouldReceive('connection')->twice()->andReturn($this->connection);

        $migrateCommand = m::mock(MigrateCommand::class);
        $seedCommand = m::mock(SeedCommand::class);

        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $this->command->setApplication($console);

        $console->shouldReceive('find')->with('migrate')->andReturn($migrateCommand);
        $console->shouldReceive('find')->with('db:seed')->andReturn($seedCommand);

        $migrateCommand->shouldReceive('run')->never();
        $seedCommand->shouldReceive('run')->once();

        $this->container->shouldReceive('call');
        $this->container->shouldReceive('environment')->once()->andReturn('testing');
        $this->container->shouldReceive('make')->once()->with('\Illuminate\Database\Console\Migrations\TableDroppers\Sqlite')->andReturn($sqlite);

        $this->command->setLaravel($this->container);

        $this->runCommand(['--database' => 'sqlite', '--migrate' => false, '--seed' => true]);

        $this->assertStringStartsWith("Dropping all tables...\nRunning seeders...", $this->output->fetch());
    }

    protected function runCommand($input)
    {
        $this->command->run(new ArrayInput($input), $this->output);
        $this->command->fire();

        $this->container->shouldHaveReceived('call')->with([$this->command, 'fire']);
    }
}
