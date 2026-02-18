<?php

namespace Illuminate\Tests\Database;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Console\CommandMutex;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationBaseCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testConnectionMigrationPathIsUsedWhenNoPathFlagGiven()
    {
        $command = new MigrateCommand($migrator = m::mock(Migrator::class), $dispatcher = m::mock(Dispatcher::class));
        $app = new ApplicationDatabaseBaseCommandStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $app->instance('config', new ConfigRepository([
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'crm' => [
                        'driver' => 'sqlsrv',
                        'migrations' => 'database/migrations/crm',
                    ],
                ],
            ],
        ]));
        $command->setLaravel($app);

        $migrator->shouldReceive('getConnection')->andReturn('crm');
        $migrator->shouldReceive('hasRunAnyMigrations')->andReturn(true);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('run')->once()->with(
            [$app->basePath().'/database/migrations/crm'],
            ['pretend' => false, 'step' => false]
        );
        $migrator->shouldReceive('getNotes')->andReturn([]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--database' => 'crm']);
    }

    public function testExplicitPathFlagTakesPrecedenceOverConnectionConfig()
    {
        $command = new MigrateCommand($migrator = m::mock(Migrator::class), $dispatcher = m::mock(Dispatcher::class));
        $app = new ApplicationDatabaseBaseCommandStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $app->instance('config', new ConfigRepository([
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'crm' => [
                        'driver' => 'sqlsrv',
                        'migrations' => 'database/migrations/crm',
                    ],
                ],
            ],
        ]));
        $command->setLaravel($app);

        $migrator->shouldReceive('getConnection')->andReturn('crm');
        $migrator->shouldReceive('hasRunAnyMigrations')->andReturn(true);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        // The explicit --path should win over the connection config
        $migrator->shouldReceive('run')->once()->with(
            [$app->basePath().'/database/migrations/custom'],
            ['pretend' => false, 'step' => false]
        );
        $migrator->shouldReceive('getNotes')->andReturn([]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--database' => 'crm', '--path' => 'database/migrations/custom']);
    }

    public function testConnectionWithoutMigrationKeyFallsBackToDefault()
    {
        $command = new MigrateCommand($migrator = m::mock(Migrator::class), $dispatcher = m::mock(Dispatcher::class));
        $app = new ApplicationDatabaseBaseCommandStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $app->instance('config', new ConfigRepository([
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'crm' => [
                        'driver' => 'sqlsrv',
                        // No 'migrations' key — should fall back to default path
                    ],
                ],
            ],
        ]));
        $command->setLaravel($app);

        $migrator->shouldReceive('getConnection')->andReturn('crm');
        $migrator->shouldReceive('paths')->once()->andReturn([]);
        $migrator->shouldReceive('hasRunAnyMigrations')->andReturn(true);
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        // Falls back to the standard database/migrations directory
        $migrator->shouldReceive('run')->once()->with(
            [__DIR__.DIRECTORY_SEPARATOR.'migrations'],
            ['pretend' => false, 'step' => false]
        );
        $migrator->shouldReceive('getNotes')->andReturn([]);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

        $this->runCommand($command, ['--database' => 'crm']);
    }

    public function testConnectionMigrationPathIsUsedForRollback()
    {
        $command = new RollbackCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseBaseCommandStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $app->instance('config', new ConfigRepository([
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'crm' => [
                        'driver' => 'sqlsrv',
                        'migrations' => 'database/migrations/crm',
                    ],
                ],
            ],
        ]));
        $command->setLaravel($app);

        $migrator->shouldReceive('getConnection')->andReturn('crm');
        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });
        $migrator->shouldReceive('setOutput')->once()->andReturn($migrator);
        $migrator->shouldReceive('rollback')->once()->with(
            [$app->basePath().'/database/migrations/crm'],
            ['pretend' => false, 'step' => 0, 'batch' => 0]
        );

        $this->runCommand($command, ['--database' => 'crm']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseBaseCommandStub extends Application
{
    public function __construct(array $data = [])
    {
        $mutex = m::mock(CommandMutex::class);
        $mutex->shouldReceive('create')->andReturn(true);
        $mutex->shouldReceive('release')->andReturn(true);
        $this->instance(CommandMutex::class, $mutex);

        foreach ($data as $abstract => $instance) {
            $this->instance($abstract, $instance);
        }
    }

    public function environment(...$environments)
    {
        return 'development';
    }
}
