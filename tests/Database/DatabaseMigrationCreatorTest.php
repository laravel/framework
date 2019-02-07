<?php

namespace Illuminate\Tests\Database;

use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Migrations\MigrationCreator;

class DatabaseMigrationCreatorTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testBasicCreateMethodStoresMigrationFile()
    {
        $creator = $this->getCreator();

        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/blank.stub')->andReturn('DummyClass');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar');

        $creator->create('create_bar', 'foo');
    }

    public function testBasicCreateMethodCallsPostCreateHooks()
    {
        $table = 'baz';

        $creator = $this->getCreator();
        unset($_SERVER['__migration.creator']);
        $creator->afterCreate(function ($table) {
            $_SERVER['__migration.creator'] = $table;
        });

        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/update.stub')->andReturn('DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

        $creator->create('create_bar', 'foo', $table);

        $this->assertEquals($_SERVER['__migration.creator'], $table);

        unset($_SERVER['__migration.creator']);
    }

    public function testTableUpdateMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/update.stub')->andReturn('DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

        $creator->create('create_bar', 'foo', 'baz');
    }

    public function testTableCreationMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/create.stub')->andReturn('DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

        $creator->create('create_bar', 'foo', 'baz', true);
    }

    public function testTableUpdateMigrationWontCreateDuplicateClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A MigrationCreatorFakeMigration class already exists.');

        $creator = $this->getCreator();

        $creator->create('migration_creator_fake_migration', 'foo');
    }

    protected function getCreator()
    {
        $files = m::mock(Filesystem::class);

        return $this->getMockBuilder(MigrationCreator::class)->setMethods(['getDatePrefix'])->setConstructorArgs([$files])->getMock();
    }
}
