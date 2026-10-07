<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Foundation\Application;
use JMac\Testing\Double;
use JMac\Testing\Matching\Argument;
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
        $events = Double::for(Dispatcher::class);
        $dispatcher = $app->instance(Dispatcher::class, $events);
        $console = Double::for(ConsoleApplication::class)->passthru();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $resetCommand = Double::for(ResetCommand::class);
        $migrateCommand = Double::for(MigrateCommand::class);

        $console->expects('find')->with('migrate:reset')->returns($resetCommand);
        $console->expects('find')->with('migrate')->returns($migrateCommand);
        $dispatcher->expects('dispatch')->with(Argument::type(DatabaseRefreshed::class));

        $quote = DIRECTORY_SEPARATOR === '\\' ? '"' : "'";
        $resetCommand->expects('run')->with(Argument::satisfies(fn ($input) => (string) $input === "--force=1 {$quote}migrate:reset{$quote}"), Argument::any());
        $migrateCommand->expects('run')->with(Argument::satisfies(fn ($input) => (string) $input === '--force=1 migrate'), Argument::any());

        $this->runCommand($command);
    }

    public function testRefreshCommandCallsCommandsWithStep()
    {
        $command = new RefreshCommand;

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $events = Double::for(Dispatcher::class);
        $dispatcher = $app->instance(Dispatcher::class, $events);
        $console = Double::for(ConsoleApplication::class)->passthru();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $rollbackCommand = Double::for(RollbackCommand::class);
        $migrateCommand = Double::for(MigrateCommand::class);

        $console->expects('find')->with('migrate:rollback')->returns($rollbackCommand);
        $console->expects('find')->with('migrate')->returns($migrateCommand);
        $dispatcher->expects('dispatch')->with(Argument::type(DatabaseRefreshed::class));

        $quote = DIRECTORY_SEPARATOR === '\\' ? '"' : "'";
        $rollbackCommand->expects('run')->with(Argument::satisfies(fn ($input) => (string) $input === "--step=2 --force=1 {$quote}migrate:rollback{$quote}"), Argument::any());
        $migrateCommand->expects('run')->with(Argument::satisfies(fn ($input) => (string) $input === '--force=1 migrate'), Argument::any());

        $this->runCommand($command, ['--step' => 2]);
    }

    public function testRefreshCommandExitsWhenProhibited()
    {
        $command = new RefreshCommand;

        $app = new ApplicationDatabaseRefreshStub(['path.database' => __DIR__]);
        $events = Double::for(Dispatcher::class);
        $dispatcher = $app->instance(Dispatcher::class, $events);
        $console = Double::for(ConsoleApplication::class)->passthru();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        RefreshCommand::prohibit();

        $code = $this->runCommand($command);

        $this->assertSame(1, $code);

        $console->received('find')->never();
        $dispatcher->expects('dispatch')->never();
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
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
