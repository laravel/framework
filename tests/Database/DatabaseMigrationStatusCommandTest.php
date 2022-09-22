<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Console\Migrations\StatusCommand;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationStatusCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testStatusCommandOutputIsInCorrectOrderWithoutPending()
    {
        $ran = [
            '2019_12_11_000000_create_users_table',
            '2019_12_13_000000_create_clients_table',
            '2019_12_12_000000_create_sellers_table',
        ];

        $batches = [
            '2019_12_11_000000_create_users_table' => 1,
            '2019_12_13_000000_create_clients_table' => 2,
            '2019_12_12_000000_create_sellers_table' => 3,
        ];

        $migrationFiles = [
            '2019_12_11_000000_create_users_table' => __DIR__.'/migrations/2019_12_11_000000_create_users_table.php',
            '2019_12_12_000000_create_sellers_table' => __DIR__.'/migrations/2019_12_12_000000_create_sellers_table.php',
            '2019_12_13_000000_create_clients_table' => __DIR__.'/migrations/2019_12_13_000000_create_clients_table.php',
        ];

        $command = new StatusCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseMigrationStatusStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
        $migrator->shouldReceive('getRepository')->andReturn($repository = m::mock(MigrationRepositoryInterface::class));
        $repository->shouldReceive('getRan')->andReturn($ran);
        $repository->shouldReceive('getMigrationBatches')->andReturn($batches);
        $migrator->shouldReceive('paths')->andReturn([]);

        $migrator->shouldReceive('getMigrationFiles')->andReturn($migrationFiles);

        $migrator->shouldReceive('getMigrationName')->andReturnValues([
            '2019_12_11_000000_create_users_table',
            '2019_12_12_000000_create_sellers_table',
            '2019_12_13_000000_create_clients_table',

            '2019_12_11_000000_create_users_table',
            '2019_12_12_000000_create_sellers_table',
            '2019_12_13_000000_create_clients_table',
        ]);

        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });

        $this->runCommand($command);

        $class = new ReflectionClass($command);

        $method = $class->getMethod('getStatusFor');

        $method->setAccessible(true);

        $command->flag = true;

        $value = $method->invoke($command, $ran, $batches);

        $this->assertEquals([
            '2019_12_11_000000_create_users_table',
            '2019_12_13_000000_create_clients_table',
            '2019_12_12_000000_create_sellers_table',
        ], $value->keys()->toArray());
    }

    public function testStatusCommandOutputIsInCorrectOrderWithPending()
    {
        $ran = [
            '2019_12_11_000000_create_users_table',
            '2019_12_13_000000_create_clients_table',
            '2019_12_12_000000_create_sellers_table',
        ];

        $batches = [
            '2019_12_11_000000_create_users_table' => 1,
            '2019_12_13_000000_create_clients_table' => 2,
            '2019_12_12_000000_create_sellers_table' => 3,
        ];

        $migrationFiles = [
            '2019_12_10_000000_jobs_table' => __DIR__.'/migrations/2019_12_10_000000_jobs_table.php',
            '2019_12_11_000000_create_users_table' => __DIR__.'/migrations/2019_12_11_000000_create_users_table.php',
            '2019_12_12_000000_create_sellers_table' => __DIR__.'/migrations/2019_12_12_000000_create_sellers_table.php',
            '2019_12_13_000000_create_clients_table' => __DIR__.'/migrations/2019_12_13_000000_create_clients_table.php',
        ];

        $command = new StatusCommand($migrator = m::mock(Migrator::class));
        $app = new ApplicationDatabaseMigrationStatusStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
        $migrator->shouldReceive('getRepository')->andReturn($repository = m::mock(MigrationRepositoryInterface::class));
        $repository->shouldReceive('getRan')->andReturn($ran);
        $repository->shouldReceive('getMigrationBatches')->andReturn($batches);
        $migrator->shouldReceive('paths')->andReturn([]);

        $migrator->shouldReceive('getMigrationFiles')->andReturn($migrationFiles);

        $migrator->shouldReceive('getMigrationName')->andReturnValues([
            '2019_12_10_000000_jobs_table',
        ]);

        $migrator->shouldReceive('usingConnection')->once()->andReturnUsing(function ($name, $callback) {
            return $callback();
        });

        $this->runCommand($command);
        $class = new ReflectionClass($command);
        $method = $class->getMethod('getStatusFor');
        $method->setAccessible(true);
        $value = $method->invoke($command, $ran, $batches);

        $this->assertEquals([
            '2019_12_11_000000_create_users_table',
            '2019_12_13_000000_create_clients_table',
            '2019_12_12_000000_create_sellers_table',
            '2019_12_10_000000_jobs_table',
        ], $value->keys()->toArray());
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseMigrationStatusStub extends Application
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
