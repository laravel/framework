<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationCreatorTest extends TestCase
{
    public function testBasicCreateMethodStoresMigrationFile()
    {
        $creator = $this->getCreator();

        $creator->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/migration.stub')->andReturn('return new class');
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'return new class');
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo');
    }

    public function testBasicCreateMethodCallsPostCreateHooks()
    {
        $table = 'baz';

        $creator = $this->getCreator();
        unset($_SERVER['__migration.creator.table'], $_SERVER['__migration.creator.path']);
        $creator->afterCreate(function ($table, $path) {
            $_SERVER['__migration.creator.table'] = $table;
            $_SERVER['__migration.creator.path'] = $path;
        });

        $creator->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.update.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/migration.update.stub')->andReturn('return new class DummyTable');
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'return new class baz');
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo', $table);

        $this->assertSame($_SERVER['__migration.creator.table'], $table);
        $this->assertSame('foo/foo_create_bar.php', $_SERVER['__migration.creator.path']);

        unset($_SERVER['__migration.creator.table'], $_SERVER['__migration.creator.path']);
    }

    public function testTableUpdateMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.update.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/migration.update.stub')->andReturn('return new class DummyTable');
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'return new class baz');
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo', 'baz');
    }

    public function testTableCreationMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.create.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/migration.create.stub')->andReturn('return new class DummyTable');
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'return new class baz');
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo', 'baz', true);
    }

    public function testTableUpdateMigrationWontCreateDuplicateClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A MigrationCreatorFakeMigration class already exists.');

        $creator = $this->getCreator();

        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $creator->create('migration_creator_fake_migration', 'foo');
    }

    public function testMigrationsCreatedWithinTheSameSecondHaveIncreasingDatePrefixes()
    {
        Carbon::setTestNow('2026-07-13 14:41:22');

        $files = new Filesystem;
        $path = sys_get_temp_dir().'/laravel-migration-creator-'.uniqid();

        try {
            $creator = new MigrationCreator($files, $path.'/stubs');

            $first = $creator->create('create_bs_table', $path, 'bs', true);
            $second = $creator->create('create_as_table', $path, 'as', true);

            $this->assertSame($path.'/2026_07_13_144122_create_bs_table.php', $first);
            $this->assertSame($path.'/2026_07_13_144123_create_as_table.php', $second);
        } finally {
            Carbon::setTestNow();
            $files->deleteDirectory($path);
        }
    }

    public function testOverriddenDatePrefixRetainsExistingBehavior()
    {
        $files = new Filesystem;
        $path = sys_get_temp_dir().'/laravel-migration-creator-'.uniqid();
        $creator = new class($files, $path.'/stubs') extends MigrationCreator
        {
            protected function getDatePrefix()
            {
                return 'custom_prefix';
            }
        };

        try {
            $first = $creator->create('create_bs_table', $path, 'bs', true);
            $second = $creator->create('create_as_table', $path, 'as', true);

            $this->assertSame($path.'/custom_prefix_create_bs_table.php', $first);
            $this->assertSame($path.'/custom_prefix_create_as_table.php', $second);
        } finally {
            $files->deleteDirectory($path);
        }
    }

    public function testOverriddenCreateMethodRetainsExistingDatePrefixBehavior()
    {
        $files = m::mock(Filesystem::class);
        $files->shouldNotReceive('glob');

        $creator = new class($files, 'stubs') extends MigrationCreator
        {
            public function create($name, $path, $table = null, $create = false)
            {
                return $this->getPath($name, $path);
            }
        };

        $this->assertMatchesRegularExpression(
            '/^foo\/\d{4}_\d{2}_\d{2}_\d{6}_create_bar\.php$/',
            $creator->create('create_bar', 'foo'),
        );
    }

    protected function getCreator()
    {
        $files = m::mock(Filesystem::class);
        $customStubs = 'stubs';

        return $this->getMockBuilder(MigrationCreator::class)
            ->onlyMethods(['getDatePrefix'])
            ->setConstructorArgs([$files, $customStubs])
            ->getMock();
    }
}
