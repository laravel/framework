<?php

namespace Illuminate\Tests\Database;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Foundation\Application;
use Illuminate\Support\Composer;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationMakeCommandTest extends TestCase
{
    public function testBasicCreateDumpsAutoload()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            $composer = m::mock(Composer::class)
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true, null)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArguments()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true, null)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenNameIsStudlyCase()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true, null)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'CreateFoo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true, null)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--create' => 'users']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenCreateTablePatternIsFound()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_users_table', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true, null)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_users_table.php');

        $this->runCommand($command, ['name' => 'create_users_table']);
    }

    public function testCanSpecifyPathToCreateMigrationsIn()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $command->setLaravel($app);
        $app->setBasePath('/home/laravel');
        $creator->shouldReceive('create')->once()
            ->with('create_foo', '/home/laravel/vendor/laravel-package/migrations', 'users', true, null)
            ->andReturn('/home/laravel/vendor/laravel-package/migrations/2021_04_23_110457_create_foo.php');
        $this->runCommand($command, ['name' => 'create_foo', '--path' => 'vendor/laravel-package/migrations', '--create' => 'users']);
    }

    public function testDatabaseOptionUsesConnectionMigrationPath()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new ApplicationDatabaseMakeCommandStub;
        $app->useDatabasePath(__DIR__);
        $app->setBasePath('/home/laravel');
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
        $creator->shouldReceive('create')->once()
            ->with('create_foo', '/home/laravel/database/migrations/crm', 'foo', true, 'crm')
            ->andReturn('/home/laravel/database/migrations/crm/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--database' => 'crm']);
    }

    public function testDatabaseOptionInjectsConnectionWhenNonDefault()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new ApplicationDatabaseMakeCommandStub;
        $app->useDatabasePath(__DIR__);
        $app->instance('config', new ConfigRepository([
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'crm' => ['driver' => 'sqlsrv'],
                ],
            ],
        ]));
        $command->setLaravel($app);
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true, 'crm')
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--database' => 'crm']);
    }

    public function testDatabaseOptionDoesNotInjectConnectionWhenDefault()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new ApplicationDatabaseMakeCommandStub;
        $app->useDatabasePath(__DIR__);
        $app->instance('config', new ConfigRepository([
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => ['driver' => 'mysql'],
                ],
            ],
        ]));
        $command->setLaravel($app);
        // $connection should be null because --database matches database.default
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true, null)
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--database' => 'mysql']);
    }

    public function testDatabaseOptionFallsBackToDefaultPathWhenNoMigrationsKey()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new ApplicationDatabaseMakeCommandStub;
        $app->useDatabasePath(__DIR__);
        $app->instance('config', new ConfigRepository([
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'crm' => ['driver' => 'sqlsrv'],  // No 'migrations' key
                ],
            ],
        ]));
        $command->setLaravel($app);
        // No 'migrations' key on connection — falls back to default database/migrations path
        $creator->shouldReceive('create')->once()
            ->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true, 'crm')
            ->andReturn(__DIR__.'/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--database' => 'crm']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class ApplicationDatabaseMakeCommandStub extends Application
{
    public function environment(...$environments)
    {
        return 'development';
    }
}
