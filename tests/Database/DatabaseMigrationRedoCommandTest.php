<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\RedoCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Events\DatabaseRefreshed;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationRedoCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRedoCommandRollsBackOneStepAndRunsMigrations()
    {
        $command = new RedoCommand;

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $dispatcher = $app->instance(Dispatcher::class, $events = m::mock());
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $rollbackCommand = m::mock(RollbackCommand::class);
        $migrateCommand = m::mock(MigrateCommand::class);

        $console->shouldReceive('find')->with('migrate:rollback')->andReturn($rollbackCommand);
        $console->shouldReceive('find')->with('migrate')->andReturn($migrateCommand);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(DatabaseRefreshed::class));

        $quote = DIRECTORY_SEPARATOR === '\\' ? '"' : "'";

        // Expect: migrate:rollback --step=1 --force then migrate --force
        $rollbackCommand->shouldReceive('run')
            ->with(new InputMatcher("--step=1 --force=1 {$quote}migrate:rollback{$quote}"), m::any());
        $migrateCommand->shouldReceive('run')
            ->with(new InputMatcher('--force=1 migrate'), m::any());

        $this->runCommand($command);
    }

    public function testRedoCommandHonoursCustomStepOption()
    {
        $command = new RedoCommand;

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $dispatcher = $app->instance(Dispatcher::class, $events = m::mock());
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $rollbackCommand = m::mock(RollbackCommand::class);
        $migrateCommand = m::mock(MigrateCommand::class);

        $console->shouldReceive('find')->with('migrate:rollback')->andReturn($rollbackCommand);
        $console->shouldReceive('find')->with('migrate')->andReturn($migrateCommand);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(DatabaseRefreshed::class));

        $quote = DIRECTORY_SEPARATOR === '\\' ? '"' : "'";

        // Expect passed step value (e.g. 3)
        $rollbackCommand->shouldReceive('run')
            ->with(new InputMatcher("--step=3 --force=1 {$quote}migrate:rollback{$quote}"), m::any());
        $migrateCommand->shouldReceive('run')
            ->with(new InputMatcher('--force=1 migrate'), m::any());

        $this->runCommand($command, ['--step' => 3]);
    }

    public function testRedoCommandExitsWhenProhibited()
    {
        $command = new RedoCommand;

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $dispatcher = $app->instance(Dispatcher::class, $events = m::mock());
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        // Re-use the global prohibition flag defined in RefreshCommand
        \Illuminate\Database\Console\Migrations\RefreshCommand::prohibit();

        $code = $this->runCommand($command);

        $this->assertSame(1, $code);

        $console->shouldNotHaveBeenCalled();
        $dispatcher->shouldNotReceive('dispatch');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
