<?php

namespace Illuminate\Tests\Database;

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
    protected function tearDown(): void
    {
        m::close();
    }

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

    public function testCreateWithBeforeOption()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        $migrationDir = __DIR__.'/migrations';
        if (!is_dir($migrationDir)) {
            mkdir($migrationDir, 0755, true);
        }
        $testMigration = $migrationDir.'/2025_07_25_145000_create_foo_table.php';
        file_put_contents($testMigration, '<?php // test migration');

        $creator->shouldReceive('create')->once()
            ->withArgs(function ($name, $path, $table, $create) use ($migrationDir) {
                return $name === 'create_bar_table'
                    && realpath($path) === realpath($migrationDir)
                    && $table === 'bar'
                    && $create === true;
            })
            ->andReturn($migrationDir.'/2025_07_25_144959_create_bar_table.php');

        $this->runCommand($command, [
            'name' => 'create_bar_table',
            '--before' => 'foo'
        ]);

        if (file_exists($testMigration)) {
            unlink($testMigration);
        }
        if (is_dir($migrationDir)) {
            rmdir($migrationDir);
        }
    }

    public function testCreateWithBeforeOptionWhenMigrationNotFound()
    {
        $command = new MigrateMakeCommand(
            $creator = m::mock(MigrationCreator::class),
            m::mock(Composer::class)->shouldIgnoreMissing()
        );
        $app = new Application;
        $app->useDatabasePath(__DIR__);
        $command->setLaravel($app);

        $creator->shouldNotReceive('create');

        $result = $this->runCommand($command, [
            'name' => 'create_bar_table',
            '--before' => 'nonexistent_foo'
        ]);

        // The command should return without creating anything
        $this->assertEquals(0, $result);
    }

    public function testTimestampCalculationWithValidMigration()
    {
        $command = new MigrateMakeCommand(
            m::mock(MigrationCreator::class),
            m::mock(Composer::class)
        );

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('calculateTimestampBefore');
        $method->setAccessible(true);

        $tempDir = sys_get_temp_dir().'/laravel_test_migrations_'.uniqid();
        mkdir($tempDir, 0755, true);

        $testMigrationFile = $tempDir.'/2025_07_25_145000_create_foo_table.php';
        file_put_contents($testMigrationFile, '<?php // test migration');

        $timestamp = $method->invoke($command, $tempDir, 'foo');

        $this->assertEquals('2025_07_25_144959', $timestamp);

        unlink($testMigrationFile);
        rmdir($tempDir);
    }

    public function testTimestampCalculationWithInvalidMigration()
    {
        $command = new MigrateMakeCommand(
            m::mock(MigrationCreator::class),
            m::mock(Composer::class)
        );

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('calculateTimestampBefore');
        $method->setAccessible(true);

        $tempDir = sys_get_temp_dir().'/laravel_test_migrations_'.uniqid();
        mkdir($tempDir, 0755, true);

        $testMigrationFile = $tempDir.'/invalid_migration_name.php';
        file_put_contents($testMigrationFile, '<?php // test migration');

        $timestamp = $method->invoke($command, $tempDir, 'invalid_foo');

        $this->assertNull($timestamp);

        unlink($testMigrationFile);
        rmdir($tempDir);
    }

    public function testTimestampCalculationWithEmptyDirectory()
    {
        $command = new MigrateMakeCommand(
            m::mock(MigrationCreator::class),
            m::mock(Composer::class)
        );

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('calculateTimestampBefore');
        $method->setAccessible(true);

        $tempDir = sys_get_temp_dir().'/laravel_test_migrations_'.uniqid();
        mkdir($tempDir, 0755, true);

        $timestamp = $method->invoke($command, $tempDir, 'foo');

        $this->assertNull($timestamp);

        rmdir($tempDir);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
