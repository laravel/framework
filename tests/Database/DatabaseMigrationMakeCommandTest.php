<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Foundation\Application;
use Illuminate\Support\Composer;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseMigrationMakeCommandTest extends TestCase
{
    public function testBasicCreateDumpsAutoload()
    {
        $creator = Double::for(MigrationCreator::class);
        $composer = Double::for(Composer::class);
        $command = new MigrateMakeCommand($creator, $composer);
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->expects('create')->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)->returns(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArguments()
    {
        $creator = Double::for(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Double::for(Composer::class)
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->expects('create')->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)->returns(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenNameIsStudlyCase()
    {
        $creator = Double::for(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Double::for(Composer::class)
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->expects('create')->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'foo', true)->returns(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'CreateFoo']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
    {
        $creator = Double::for(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Double::for(Composer::class)
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->expects('create')->with('create_foo', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true)->returns(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_foo.php');

        $this->runCommand($command, ['name' => 'create_foo', '--create' => 'users']);
    }

    public function testBasicCreateGivesCreatorProperArgumentsWhenCreateTablePatternIsFound()
    {
        $creator = Double::for(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Double::for(Composer::class)
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);
        $creator->expects('create')->with('create_users_table', __DIR__.DIRECTORY_SEPARATOR.'migrations', 'users', true)->returns(__DIR__.'/Fixtures/migrations/2021_04_23_110457_create_users_table.php');

        $this->runCommand($command, ['name' => 'create_users_table']);
    }

    public function testCanSpecifyPathToCreateMigrationsIn()
    {
        $creator = Double::for(MigrationCreator::class);
        $command = new MigrateMakeCommand(
            $creator,
            Double::for(Composer::class)
        );
        $app = new Application;
        $command->setLaravel($app);
        $app->setBasePath('/home/laravel');
        $creator->expects('create')->with('create_foo', '/home/laravel/vendor/laravel-package/migrations', 'users', true)->returns('/home/laravel/vendor/laravel-package/migrations/2021_04_23_110457_create_foo.php');
        $this->runCommand($command, ['name' => 'create_foo', '--path' => 'vendor/laravel-package/migrations', '--create' => 'users']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
