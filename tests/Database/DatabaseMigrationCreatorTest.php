<?php

use Mockery as m;

class DatabaseMigrationCreatorTest extends PHPUnit_Framework_TestCase
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
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->getStubPath().'/blank.stub')->andReturn('DummyClass');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar');

        $creator->create('create_bar', 'foo');

        $this->assertTrue($_SERVER['__migration.creator']);

        unset($_SERVER['__migration.creator']);
    }

    public function testTableUpdateMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->getStubPath().'/update.stub')->andReturn('DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

        $creator->create('create_bar', 'foo', 'baz');
    }

    public function testTableCreationMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->will($this->returnValue('foo'));
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->getStubPath().'/create.stub')->andReturn('DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');

        $creator->create('create_bar', 'foo', 'baz', true);
    }

    public function testTableUpdateMigrationWontCreateDuplicateClass()
    {
        $creator = $this->getCreator();
        eval("class UpdateBar{}");

        try {
            $creator->create('update_bar', 'foo');
        } catch(\Exception $e) {
            $this->assertEquals($e->getMessage(), "UpdateBar already exists");
            return;
        }

        $this->fail();
    }

    protected function getCreator()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');

        return $this->getMock('Illuminate\Database\Migrations\MigrationCreator', ['getDatePrefix'], [$files]);
    }
}
