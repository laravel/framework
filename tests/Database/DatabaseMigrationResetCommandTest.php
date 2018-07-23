<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Database\Console\Migrations\ResetCommand;

class DatabaseMigrationResetCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testResetCommandCallsMigratorWithProperArguments()
    {
        $command = new ResetCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
        $app = new ApplicationDatabaseResetStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('setConnection')->once()->with(null);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
        $migrator->shouldReceive('reset')->once()->with([__DIR__.'/migrations'], false);
        $migrator->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command);
    }

    public function testResetCommandCanBePretended()
    {
        $command = new ResetCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
        $app = new ApplicationDatabaseResetStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('setConnection')->once()->with('foo');
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
        $migrator->shouldReceive('reset')->once()->with([__DIR__.'/migrations'], true);
        $migrator->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new \Symfony\Component\Console\Input\ArrayInput($input), new \Symfony\Component\Console\Output\NullOutput);
    }
}

class ApplicationDatabaseResetStub extends Application
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
