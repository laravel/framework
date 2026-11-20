<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationCreatorTest extends TestCase
{
    use VerifiesDoubles;

    #[AllowMockObjectsWithoutExpectations]
    public function testBasicCreateMethodStoresMigrationFile()
    {
        $creator = $this->getCreator();

        $creator->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->expects('exists')->with('stubs/migration.stub')->returns(false);
        $creator->getFilesystem()->expects('get')->with($creator->stubPath().'/migration.stub')->returns('return new class');
        $creator->getFilesystem()->expects('ensureDirectoryExists')->with('foo');
        $creator->getFilesystem()->expects('put')->with('foo/foo_create_bar.php', 'return new class');
        $creator->getFilesystem()->expects('glob')->with('foo/*.php')->returns(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->expects('requireOnce')->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo');
    }

    #[AllowMockObjectsWithoutExpectations]
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
        $creator->getFilesystem()->expects('exists')->with('stubs/migration.update.stub')->returns(false);
        $creator->getFilesystem()->expects('get')->with($creator->stubPath().'/migration.update.stub')->returns('return new class DummyTable');
        $creator->getFilesystem()->expects('ensureDirectoryExists')->with('foo');
        $creator->getFilesystem()->expects('put')->with('foo/foo_create_bar.php', 'return new class baz');
        $creator->getFilesystem()->expects('glob')->with('foo/*.php')->returns(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->expects('requireOnce')->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo', $table);

        $this->assertEquals($_SERVER['__migration.creator.table'], $table);
        $this->assertSame('foo/foo_create_bar.php', $_SERVER['__migration.creator.path']);

        unset($_SERVER['__migration.creator.table'], $_SERVER['__migration.creator.path']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testTableUpdateMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->expects('exists')->with('stubs/migration.update.stub')->returns(false);
        $creator->getFilesystem()->expects('get')->with($creator->stubPath().'/migration.update.stub')->returns('return new class DummyTable');
        $creator->getFilesystem()->expects('ensureDirectoryExists')->with('foo');
        $creator->getFilesystem()->expects('put')->with('foo/foo_create_bar.php', 'return new class baz');
        $creator->getFilesystem()->expects('glob')->with('foo/*.php')->returns(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->expects('requireOnce')->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo', 'baz');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testTableCreationMigrationStoresMigrationFile()
    {
        $creator = $this->getCreator();
        $creator->method('getDatePrefix')->willReturn('foo');
        $creator->getFilesystem()->expects('exists')->with('stubs/migration.create.stub')->returns(false);
        $creator->getFilesystem()->expects('get')->with($creator->stubPath().'/migration.create.stub')->returns('return new class DummyTable');
        $creator->getFilesystem()->expects('ensureDirectoryExists')->with('foo');
        $creator->getFilesystem()->expects('put')->with('foo/foo_create_bar.php', 'return new class baz');
        $creator->getFilesystem()->expects('glob')->with('foo/*.php')->returns(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->expects('requireOnce')->with('foo/foo_create_bar.php');

        $creator->create('create_bar', 'foo', 'baz', true);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testTableUpdateMigrationWontCreateDuplicateClass()
    {
        $this->expectExceptionObject(new InvalidArgumentException('A MigrationCreatorFakeMigration class already exists.'));

        $creator = $this->getCreator();

        $creator->getFilesystem()->expects('glob')->with('foo/*.php')->returns(['foo/foo_create_bar.php']);
        $creator->getFilesystem()->expects('requireOnce')->with('foo/foo_create_bar.php');

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
        $files = Double::for(Filesystem::class);
        $files->expects('glob')->never();

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
        $files = Double::for(Filesystem::class);
        $customStubs = 'stubs';

        return $this->getMockBuilder(MigrationCreator::class)
            ->onlyMethods(['getDatePrefix'])
            ->setConstructorArgs([$files, $customStubs])
            ->getMock();
    }
}
