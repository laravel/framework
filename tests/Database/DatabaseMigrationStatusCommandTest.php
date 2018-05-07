<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Tester\CommandTester;
use Illuminate\Database\Console\Migrations\StatusCommand;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Symfony\Component\Console\Application as ConsoleApplication;

class DatabaseMigrationStatusCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testStatusCommandOutputsMigrationsInCorrectOrder()
    {
        $expected = <<<'TABLE'
+------+------------------------------------------------+-------+
| Ran? | Migration                                      | Batch |
+------+------------------------------------------------+-------+
| Y    | 2016_01_01_000000_create_users_table           | 1     |
| Y    | 2016_01_01_200000_create_flights_table         | 2     |
| N    | 2016_01_01_100000_create_password_resets_table |       |
+------+------------------------------------------------+-------+

TABLE;
        $migratorRepository = $this->createMock(MigrationRepositoryInterface::class);

        $migratorRepository->expects($this->once())
            ->method('getRan')
            ->will($this->returnValue([
                '2016_01_01_000000_create_users_table',
                '2016_01_01_200000_create_flights_table',
            ]));

        $migratorRepository->expects($this->once())
            ->method('getMigrationBatches')
            ->will($this->returnValue([
                '2016_01_01_000000_create_users_table' => 1,
                '2016_01_01_200000_create_flights_table' => 2,
            ]));

        $migrator = $this->createMock(Migrator::class);

        $migrator->expects($this->once())
            ->method('repositoryExists')
            ->willReturn(true);

        $migrator->expects($this->once())
            ->method('paths')
            ->willReturn([
                __DIR__.'/migrations/one',
                __DIR__.'/migrations/two',
            ]);

        $migrator->method('getMigrationName')
            ->will($this->returnValueMap([
                [__DIR__.'/migrations/one/2016_01_01_000000_create_users_table.php', '2016_01_01_000000_create_users_table'],
                [__DIR__.'/migrations/one/2016_01_01_100000_create_password_resets_table.php', '2016_01_01_100000_create_password_resets_table'],
                [__DIR__.'/migrations/two/2016_01_01_200000_create_flights_table.php', '2016_01_01_200000_create_flights_table'],
            ]));

        $migrator->expects($this->once())
            ->method('getMigrationFiles')
            ->will($this->returnValue([
                '2016_01_01_000000_create_users_table' => __DIR__.'/migrations/one/2016_01_01_000000_create_users_table.php',
                '2016_01_01_100000_create_password_resets_table' => __DIR__.'/migrations/one/2016_01_01_100000_create_password_resets_table.php',
                '2016_01_01_200000_create_flights_table' => __DIR__.'/migrations/two/2016_01_01_200000_create_flights_table.php',
            ]));

        $migrator->expects($this->once())
            ->method('setConnection');

        $migrator->expects($this->atLeastOnce())
            ->method('getRepository')
            ->willReturn($migratorRepository);

        $command = new StatusCommand($migrator);

        $app = new ApplicationDatabaseStatusStub(['path.database' => __DIR__]);
        $console = m::mock(ConsoleApplication::class)->makePartial();
        $console->__construct();
        $command->setLaravel($app);
        $command->setApplication($console);

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();

        $this->assertEquals($expected, $output);
    }
}

class ApplicationDatabaseStatusStub extends Application
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
