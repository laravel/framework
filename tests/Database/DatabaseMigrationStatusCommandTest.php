<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Illuminate\Database\Console\Migrations\StatusCommand;

class DatabaseMigrationStatusCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testStatusCommandOutputsOnlyPendingMigrations()
    {
        $command = new StatusCommand($migrator = $this->createMock('Illuminate\Database\Migrations\Migrator'));
        $app = new ApplicationDatabaseStatusStub(['path.database' => __DIR__]);
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        $migrator->expects($this->once())
                 ->method('setConnection');

        $migrator->expects($this->once())
                 ->method('repositoryExists')
                 ->will($this->returnValue(true));

        $migrator->expects($this->atLeastOnce())
                 ->method('getRepository')
                 ->will($this->returnValue($repository = $this->createMock('Illuminate\Database\Migrations\MigrationRepositoryInterface')));

        $migrator->expects($this->atLeastOnce())
                 ->method('getMigrationName')
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
                 ->method('paths')
                 ->will($this->returnValue([]));

        $repository->expects($this->once())
                   ->method('getRan')
                   ->will($this->returnValue([
                       '2016_01_01_000000_create_users_table',
                       '2016_01_01_100000_create_password_resets_table',
                   ]));

        $repository->expects($this->once())
                   ->method('getMigrationBatches')
                   ->will($this->returnValue([
                       '2016_01_01_000000_create_users_table' => 1,
                       '2016_01_01_100000_create_password_resets_table' => 1,
                   ]));

        $tester = new CommandTester($command);
        $tester->execute(['--pending' => true]);

        $expected = <<<'OUTPUT'
+------+----------------------------------------+-------+
| Ran? | Migration                              | Batch |
+------+----------------------------------------+-------+
| No   | 2016_01_01_200000_create_flights_table |       |
+------+----------------------------------------+-------+

OUTPUT;

        $this->assertEquals($expected, $tester->getDisplay(true));
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
