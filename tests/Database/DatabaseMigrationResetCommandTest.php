<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationResetCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        ResetCommand::prohibit(false);
    }

    public function testResetCommandCallsMigratorWithProperArguments()
    {
        $migrator = Mockery::mock(Migrator::class);
        $command = new ResetCommand($migrator);
        $app = new ApplicationDatabaseResetStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('usingConnection')->with(null, Mockery::type(Closure::class))->andReturnUsing(function ($connection, $callback) {
            $callback();
        });
        $migrator->expects('repositoryExists')->andReturn(true);
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('reset')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], false);

        $this->runCommand($command);
    }

    public function testResetCommandCanBePretended()
    {
        $migrator = Mockery::mock(Migrator::class);
        $command = new ResetCommand($migrator);
        $app = new ApplicationDatabaseResetStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->expects('paths')->andReturn([]);
        $migrator->expects('usingConnection')->with('foo', Mockery::type(Closure::class))->andReturnUsing(function ($connection, $callback) {
            $callback();
        });
        $migrator->expects('repositoryExists')->andReturn(true);
        $migrator->expects('setOutput')->andReturn($migrator);
        $migrator->expects('reset')->with([__DIR__.DIRECTORY_SEPARATOR.'migrations'], true);

        $this->runCommand($command, ['--pretend' => true, '--database' => 'foo']);
    }

    public function testRefreshCommandExitsWhenProhibited()
    {
        $migrator = Mockery::mock(Migrator::class);
        $command = new ResetCommand($migrator);

        $app = new ApplicationDatabaseResetStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        ResetCommand::prohibit();

        $code = $this->runCommand($command);

        $this->assertSame(1, $code);

        $migrator->shouldNotHaveReceived('paths');
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
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

    public function environment(...$environments)
    {
        return 'development';
    }
}
