<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Symfony\Component\Console\Application as ConsoleApplication;

class DatabaseMigrationRefreshCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRefreshCommandCallsCommandsWithProperArguments()
    {
        $command = new RefreshCommand($migrator = m::mock(Migrator::class));

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $resetCommand = m::mock(ResetCommand::class);
        $migrateCommand = m::mock(MigrateCommand::class);

        $console->shouldReceive('find')->with('migrate:reset')->andReturn($resetCommand);
        $console->shouldReceive('find')->with('migrate')->andReturn($migrateCommand);

        $quote = DIRECTORY_SEPARATOR == '\\' ? '"' : "'";
        $resetCommand->shouldReceive('run')->with(new InputMatcher("--database --path --realpath --force {$quote}migrate:reset{$quote}"), m::any());
        $migrateCommand->shouldReceive('run')->with(new InputMatcher('--database --path --realpath --force migrate'), m::any());

        $this->runCommand($command);
    }

    public function testRefreshCommandCallsCommandsWithStep()
    {
        $command = new RefreshCommand($migrator = m::mock(Migrator::class));

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $rollbackCommand = m::mock(RollbackCommand::class);
        $migrateCommand = m::mock(MigrateCommand::class);

        $console->shouldReceive('find')->with('migrate:rollback')->andReturn($rollbackCommand);
        $console->shouldReceive('find')->with('migrate')->andReturn($migrateCommand);

        $quote = DIRECTORY_SEPARATOR == '\\' ? '"' : "'";
        $rollbackCommand->shouldReceive('run')->with(new InputMatcher("--database --path --realpath --step=2 --force {$quote}migrate:rollback{$quote}"), m::any());
        $migrateCommand->shouldReceive('run')->with(new InputMatcher('--database --path --realpath --force migrate'), m::any());

        $this->runCommand($command, ['--step' => 2]);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class InputMatcher extends m\Matcher\MatcherAbstract
{
    /**
     * @param  \Symfony\Component\Console\Input\ArrayInput  $actual
     * @return bool
     */
    public function match(&$actual)
    {
        return (string) $actual == $this->_expected;
    }

    public function __toString()
    {
        return '';
    }
}

class ApplicationDatabaseRefreshStub extends Application
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
