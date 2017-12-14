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

    public function testBasicCreateMethodStoresBlankMigrationFile()
    {
        $creator = $this->getCreator();
        unset($_SERVER['__migration.creator']);
        $creator->afterCreate(function () {
            $_SERVER['__migration.creator'] = true;
        });
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/blank.stub')->andReturn('blank: DummyClass');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_migration_name.php', 'blank: MigrationName');

        $creator->create('migration_name', 'foo');

        $this->assertTrue($_SERVER['__migration.creator']);

        unset($_SERVER['__migration.creator']);
    }

    public function testBasicCreateMethodStoresUpdateMigrationFileWhenPassedTable()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/update.stub')->andReturn('update: DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_migration_name.php', 'update: MigrationName baz');

        $creator->create('migration_name', 'foo', 'baz');
    }

    public function testBasicCreateMethodStoresCreateMigrationFileWhenPassedTableAndCreate()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/create.stub')->andReturn('create: DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_migration_name.php', 'create: MigrationName baz');

        $creator->create('migration_name', 'foo', 'baz', true);
    }

    public function testAdvancedCreateMethodStoresCreateMigration()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/create.stub')->andReturn('create: DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_create_users_table.php', 'create: CreateUsersTable users');

        $creator->create('create_users_table', 'foo');
    }

    public function testAdvancedCreateMethodStoresDropMigration()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/drop.stub')->andReturn('drop: DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_drop_users_table.php', 'drop: DropUsersTable users');

        $creator->create('drop_users_table', 'foo');
    }

    public function testAdvancedCreateMethodStoresRenameTableMigration()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/rename-table.stub')->andReturn('rename-table: DummyClass DummyTable DummyTableFrom DummyTableTo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_rename_bar_table_to_baz_table.php', 'rename-table: RenameBarTableToBazTable DummyTable bar baz');

        $creator->create('rename_bar_table_to_baz_table', 'foo');
    }

    public function testAdvancedCreateMethodStoresRenameTableMigrationWithoutTableSuffix()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/rename-table.stub')->andReturn('rename-table: DummyClass DummyTable DummyTableFrom DummyTableTo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_rename_bar_to_baz.php', 'rename-table: RenameBarToBaz DummyTable bar baz');

        $creator->create('rename_bar_to_baz', 'foo');
    }

    public function testAdvancedCreateMethodStoresAddColumnTableMigration()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/add.stub')->andReturn('add: DummyClass DummyTable DummyColumn');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_add_email_to_users_table.php', 'add: AddEmailToUsersTable users email');

        $creator->create('add_email_to_users_table', 'foo');
    }

    public function testAdvancedCreateMethodStoresRemoveColumnTableMigration()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/remove.stub')->andReturn('remove: DummyClass DummyTable DummyColumn');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_remove_email_from_users_table.php', 'remove: RemoveEmailFromUsersTable users email');

        $creator->create('remove_email_from_users_table', 'foo');
    }

    public function testAdvancedCreateMethodStoresRenameColumnTableMigration()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('12345'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/rename-column.stub')->andReturn('rename-column: DummyClass DummyTable DummyColumn DummyColumnFrom DummyColumnTo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/12345_rename_bar_to_baz_in_users.php', 'rename-column: RenameBarToBazInUsers users DummyColumn bar baz');

        $creator->create('rename_bar_to_baz_in_users', 'foo');
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
