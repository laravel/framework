<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationCreatorTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicCreateMethodStoresMigrationFile()
    {
        $creator = $this->getCreator();
        unset($_SERVER['__migration.creator']);
        $creator->afterCreate(function () {
            $_SERVER['__migration.creator'] = true;
        });
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/blank.stub')->andReturn('DummyClass');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar');

        $creator->create('create_bar', 'foo');

        $this->assertTrue($_SERVER['__migration.creator']);

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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A MigrationCreatorFakeMigration class already exists.
     */
    public function testTableUpdateMigrationWontCreateDuplicateClass()
    {
        $creator = $this->getCreator();

        $creator->create('migration_creator_fake_migration', 'foo');
    }

    protected function getCreator()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');

        return $this->getMockBuilder('Illuminate\Database\Migrations\MigrationCreator')->setMethods(['getDatePrefix'])->setConstructorArgs([$files])->getMock();
    }
}
