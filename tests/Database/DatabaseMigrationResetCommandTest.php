<?php

use Mockery as m;
use Illuminate\Database\Console\Migrations\ResetCommand;

class DatabaseMigrationResetCommandTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testResetCommandCallsMigratorWithProperArguments()
    {
        $command = new ResetCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
        $command->setLaravel(new AppDatabaseMigrationStub());
        $migrator->shouldReceive('setConnection')->once()->with(null);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
        $migrator->shouldReceive('reset')->once()->with(false);
        $migrator->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command);
    }

    public function testResetCommandCanBePretended()
    {
        $command = new ResetCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
        $command->setLaravel(new AppDatabaseMigrationStub());
        $migrator->shouldReceive('setConnection')->once()->with('foo');
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
        $migrator->shouldReceive('reset')->once()->with(true);
        $migrator->shouldReceive('getNotes')->andReturn([]);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
    }
}

class AppDatabaseMigrationStub extends Illuminate\Foundation\Application
{
    public function environment()
    {
        return 'development';
    }
}
