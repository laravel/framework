<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Foundation\Application;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationRefreshCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        RefreshCommand::prohibit(false);
    }

    public function testRefreshCommandCallsCommandsWithProperArguments()
    {
        $command = new RefreshCommand;

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $events = Mockery::mock();
        $dispatcher = $app->instance(Dispatcher::class, $events);
        $console = Mockery::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $resetCommand = Mockery::mock(ResetCommand::class);
        $migrateCommand = Mockery::mock(MigrateCommand::class);

        $console->expects('find')->with('migrate:reset')->andReturn($resetCommand);
        $console->expects('find')->with('migrate')->andReturn($migrateCommand);
        $dispatcher->expects('dispatch')->with(Mockery::type(DatabaseRefreshed::class));

        $quote = DIRECTORY_SEPARATOR === '\\' ? '"' : "'";
        $resetCommand->expects('run')->with(new InputMatcher("--force=1 {$quote}migrate:reset{$quote}"), Mockery::any());
        $migrateCommand->expects('run')->with(new InputMatcher('--force=1 migrate'), Mockery::any());

        $this->runCommand($command);
    }

    public function testRefreshCommandCallsCommandsWithStep()
    {
        $command = new RefreshCommand;

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $events = Mockery::mock();
        $dispatcher = $app->instance(Dispatcher::class, $events);
        $console = Mockery::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $rollbackCommand = Mockery::mock(RollbackCommand::class);
        $migrateCommand = Mockery::mock(MigrateCommand::class);

        $console->expects('find')->with('migrate:rollback')->andReturn($rollbackCommand);
        $console->expects('find')->with('migrate')->andReturn($migrateCommand);
        $dispatcher->expects('dispatch')->with(Mockery::type(DatabaseRefreshed::class));

        $quote = DIRECTORY_SEPARATOR === '\\' ? '"' : "'";
        $rollbackCommand->expects('run')->with(new InputMatcher("--step=2 --force=1 {$quote}migrate:rollback{$quote}"), Mockery::any());
        $migrateCommand->expects('run')->with(new InputMatcher('--force=1 migrate'), Mockery::any());

        $this->runCommand($command, ['--step' => 2]);
    }

    public function testRefreshCommandExitsWhenProhibited()
    {
        $command = new RefreshCommand;

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $events = Mockery::mock();
        $dispatcher = $app->instance(Dispatcher::class, $events);
        $console = Mockery::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        RefreshCommand::prohibit();

        $code = $this->runCommand($command);

        $this->assertSame(1, $code);

        $console->shouldNotHaveReceived('find');
        $dispatcher->shouldNotReceive('dispatch');
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class InputMatcher extends Mockery\Matcher\MatcherAbstract
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
