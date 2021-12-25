<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\MigrationLine;
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
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/migration.stub')->andReturn('DummyClass');
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar');
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

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

        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.update.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/migration.update.stub')->andReturn('DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo', $table);

        $this->assertEquals($_SERVER['__migration.creator'], $table);

        unset($_SERVER['__migration.creator']);
    }

    public function testTableUpdateMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.update.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/migration.update.stub')->andReturn('DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo', 'baz');
    }

    public function testTableCreationMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.create.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath().'/migration.create.stub')->andReturn('DummyClass DummyTable');
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', 'CreateBar baz');
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

    public function testTableCeateMigrationWillAddColumnsIfDefined()
    {
        $columns = [new MigrationLine('fake_string', 'string')];
        $fakeStub = 'DummyClass DummyTable {{ columns }}';
        $populatedFakeStub = "CreateBar baz \$table->string('fake_string');\n";

        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.create.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath() . '/migration.create.stub')->andReturn($fakeStub);
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', $populatedFakeStub);
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');
        $creator->create('create_bar', 'foo', 'baz', true, $columns);
    }

    public function testTableCeateMigrationWillAddColumnsWithChainedMethodsIfDefined()
    {
        $columns = [new MigrationLine('fake_int', 'integer', ['index', 'primary'])];
        $fakeStub = 'DummyClass DummyTable {{ columns }}';
        $populatedFakeStub = "CreateBar baz \$table->integer('fake_int')->index()->primary();\n";

        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.create.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath() . '/migration.create.stub')->andReturn($fakeStub);
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', $populatedFakeStub);
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');
        $creator->create('create_bar', 'foo', 'baz', true, $columns);
    }

    public function testTableCeateMigrationWillAddMultipleColumnsIfDefined()
    {
        $columns = [new MigrationLine('fake_string', 'string'), new MigrationLine('fake_integer', 'integer')];
        $fakeStub = <<<'stub'
            DummyClass DummyTable 
            {{ columns }}
        stub;

        $populatedFakeStub = <<<'stub'
            CreateBar baz 
            $table->string('fake_string');
            $table->integer('fake_integer');

        stub;

        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.create.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath() . '/migration.create.stub')->andReturn($fakeStub);
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', $populatedFakeStub);
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');
        $creator->create('create_bar', 'foo', 'baz', true, $columns);
    }

    public function testTableCeateMigrationWillAddMultipleColumnsIfDefinedWithReverts()
    {
        $columns = [new MigrationLine('fake_string', 'string'), new MigrationLine('fake_integer', 'integer')];
        $fakeStub = <<<'stub'
            DummyClass DummyTable 
            {{ columns }}
            {{ revert_columns }}
        stub;

        $populatedFakeStub = <<<'stub'
            CreateBar baz 
            $table->string('fake_string');
            $table->integer('fake_integer');
            $table->dropColumn('fake_string');
            $table->dropColumn('fake_integer');

        stub;

        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.create.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath() . '/migration.create.stub')->andReturn($fakeStub);
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', $populatedFakeStub);
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');
        $creator->create('create_bar', 'foo', 'baz', true, $columns);
    }

    public function testTableCeateMigrationWillRemovePlaceholdersIfNoColumnsAreDefined()
    {
        $columns = [];
        $fakeStub = <<<'stub'
            DummyClass DummyTable
            {{ columns }}
            {{ revert_columns }}
        stub;

        $populatedFakeStub = <<<'stub'
            CreateBar baz

        stub;

        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.create.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath() . '/migration.create.stub')->andReturn($fakeStub);
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->once()->with('foo/foo_create_bar.php', $populatedFakeStub);
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');
        $creator->create('create_bar', 'foo', 'baz', true, $columns);
    }

    public function testTableCeateMigrationFailsIfColumnTypeIsNotValid()
    {
        $columns = [new MigrationLine('date', 'not_a_type')];
        $fakeStub = <<<'stub'
            DummyClass DummyTable
            {{ columns }}
            {{ revert_columns }}
        stub;

        $populatedFakeStub = <<<'stub'
            CreateBar baz

        stub;

        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.create.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath() . '/migration.create.stub')->andReturn($fakeStub);
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->with('foo/foo_create_bar.php', $populatedFakeStub);
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The method not_a_type for column date is invalid');
        $creator->create('create_bar', 'foo', 'baz', true, $columns);
    }

    public function testTableCeateMigrationFailsIfOneOfTheColumnChainedMethodsNotValid()
    {
        $columns = [new MigrationLine('date', 'timestamp', ['not_a_type'])];
        $fakeStub = <<<'stub'
            DummyClass DummyTable
            {{ columns }}
            {{ revert_columns }}
        stub;

        $populatedFakeStub = <<<'stub'
            CreateBar baz

        stub;

        $creator = $this->getCreator();
        $creator->expects($this->any())->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->shouldReceive('exists')->once()->with('stubs/migration.create.stub')->andReturn(false);
        $creator->getFilesystem()->shouldReceive('get')->once()->with($creator->stubPath() . '/migration.create.stub')->andReturn($fakeStub);
        $creator->getFilesystem()->shouldReceive('ensureDirectoryExists')->once()->with('foo');
        $creator->getFilesystem()->shouldReceive('put')->with('foo/foo_create_bar.php', $populatedFakeStub);
        $creator->getFilesystem()->shouldReceive('glob')->once()->with('foo/*.php')->andReturn(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->shouldReceive('requireOnce')->once()->with('foo/foo_create_bar.php');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The chained method not_a_type for column date is invalid');
        $creator->create('create_bar', 'foo', 'baz', true, $columns);
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
