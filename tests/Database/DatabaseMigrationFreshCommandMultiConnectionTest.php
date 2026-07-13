<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationFreshCommandMultiConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        FreshCommand::prohibit(false);

        parent::tearDown();
    }

    public function testFreshWipesEveryConnectionUsedByPendingMigrations()
    {
        $command = new FreshCommand($migrator = m::mock(Migrator::class));

        $app = new ApplicationDatabaseFreshMultiConnStub(['path.database' => __DIR__]);
        $dispatcher = $app->instance(Dispatcher::class, m::mock());

        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();

        $command->setLaravel($app);
        $command->setApplication($console);

        $migrator->shouldReceive('paths')->once()->andReturn([]);

        $migrator->shouldReceive('usingConnection')
            ->once()
            ->andReturnUsing(function ($name, $callback) {
                return $callback();
            });

        $migrator->shouldReceive('repositoryExists')
            ->once()
            ->andReturn(true);

        $migrator->shouldReceive('getConnectionsForPendingMigrations')
            ->once()
            ->andReturn(['mysql', 'mysql2']);

        $wipeCommand = m::mock(WipeCommand::class);
        $migrateCommand = m::mock(MigrateCommand::class);

        $console->shouldReceive('find')->with('db:wipe')->andReturn($wipeCommand);
        $console->shouldReceive('find')->with('migrate')->andReturn($migrateCommand);

        $wipeCommand->shouldReceive('run')
            ->with(
                m::on(function (ArrayInput $input) {
                    $string = (string) $input;

                    return str_contains($string, 'db:wipe')
                        && str_contains($string, '--force')
                        && str_contains($string, '--database=mysql')
                        && ! str_contains($string, '--database=mysql2');
                }),
                m::any()
            )
            ->once()
            ->andReturn(0);

        $wipeCommand->shouldReceive('run')
            ->with(
                m::on(function (ArrayInput $input) {
                    $string = (string) $input;

                    return str_contains($string, 'db:wipe')
                        && str_contains($string, '--force')
                        && str_contains($string, '--database=mysql2');
                }),
                m::any()
            )
            ->once()
            ->andReturn(0);

        $migrateCommand->shouldReceive('run')
            ->with(
                m::on(function ($input) {
                    $string = (string) $input;

                    return str_contains($string, 'migrate') && str_contains($string, '--force');
                }),
                m::any()
            )
            ->once()
            ->andReturn(0);

        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(m::type(DatabaseRefreshed::class));

        $this->runCommand($command);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseFreshMultiConnStub extends Application
{
    public function __construct(array $data = [])
    {
        parent::__construct();

        foreach ($data as $abstract => $instance) {
            $this->instance($abstract, $instance);
        }
    }

    public function environment(...$environments)
    {
        return 'development';
    }
}
