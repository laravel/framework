<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Database\Console\Migrations\MigrateCommand;

class DatabaseMigrationMigrateCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicMigrationsCallMigratorWithProperArguments()
    {
        $command = new MigrateCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'), __DIR__.'/vendor');
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('setConnection')->once()->with(null);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->shouldReceive('getNotes')->andReturn([]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command);
    }

    public function testMigrationRepositoryCreatedWhenNecessary()
    {
        $params = [$migrator = m::mock('Illuminate\Database\Migrations\Migrator'), __DIR__.'/vendor'];
        $command = $this->getMockBuilder('Illuminate\Database\Console\Migrations\MigrateCommand')->setMethods(['call'])->setConstructorArgs($params)->getMock();
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('setConnection')->once()->with(null);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->shouldReceive('getNotes')->andReturn([]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(false);
        $command->expects($this->once())->method('call')->with($this->equalTo('migrate:install'), $this->equalTo(['--database' => null]));

        $this->runCommand($command);
    }

    public function testTheCommandMayBePretended()
    {
        $command = new MigrateCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'), __DIR__.'/vendor');
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('setConnection')->once()->with(null);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => true, 'step' => false]);
        $migrator->shouldReceive('getNotes')->andReturn([]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--pretend' => true]);
    }

    public function testTheDatabaseMayBeSet()
    {
        $command = new MigrateCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'), __DIR__.'/vendor');
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('setConnection')->once()->with('foo');
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => false]);
        $migrator->shouldReceive('getNotes')->andReturn([]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--database' => 'foo']);
    }

    public function testStepMayBeSet()
    {
        $command = new MigrateCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'), __DIR__.'/vendor');
        $app = new ApplicationDatabaseMigrationStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('setConnection')->once()->with(null);
        $migrator->shouldReceive('run')->once()->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], ['pretend' => false, 'step' => true]);
        $migrator->shouldReceive('getNotes')->andReturn([]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--step' => true]);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new \Symfony\Component\Console\Input\ArrayInput($input), new \Symfony\Component\Console\Output\NullOutput);
    }
}

class ApplicationDatabaseMigrationStub extends Application
{
    public function __construct(array $data = [])
    {
        foreach ($data as $abstract => $instance) {
            $this->instance($abstract, $instance);
        }
    }

    public function environment()
    {
        return 'development';
    }
}
