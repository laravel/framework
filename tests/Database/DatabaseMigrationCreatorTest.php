<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationCreatorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicCreateMethodStoresMigrationFile()
    {
        $creator = $this->getCreator();

        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
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

        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.update.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/migration.update.stub')->andReturn('return new class DummyTable');
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'return new class baz');
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo', $table);

        $this->assertEquals($_SERVER['__migration.creator.table'], $table);
        $this->assertEquals($_SERVER['__migration.creator.path'], 'foo/foo_create_bar.php');

        unset($_SERVER['__migration.creator.table'], $_SERVER['__migration.creator.path']);
    }

    public function testTableUpdateMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
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
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
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
