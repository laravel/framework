<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\StrayDiskUsageException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class FilesystemStrayDiskUsageTest extends TestCase
{
    /**
     * The root directory of the "temp" disk used throughout the test.
     *
     * @var string
     */
    protected $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/laravel-stray-disk-usage';

        Config::set('filesystems.disks.temp', [
            'driver' => 'local',
            'root' => $this->root,
        ]);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->root);
        (new Filesystem)->deleteDirectory(storage_path('framework/testing/disks/temp'));
        (new Filesystem)->deleteDirectory(storage_path('framework/testing/disks/public'));

        parent::tearDown();
    }

    public function testStrayDiskUsageIsNotPreventedByDefault()
    {
        $this->assertFalse(Storage::preventingStrayDiskUsage());

        Storage::disk('temp')->put('file.txt', 'contents');

        $this->assertSame('contents', file_get_contents($this->root.'/file.txt'));
    }

    public function testPreventingStrayDiskUsageMayBeTurnedBackOff()
    {
        Storage::preventStrayDiskUsage();

        $this->assertTrue(Storage::preventingStrayDiskUsage());

        Storage::preventStrayDiskUsage(false);

        $this->assertFalse(Storage::preventingStrayDiskUsage());

        Storage::disk('temp')->put('file.txt', 'contents');

        $this->assertSame('contents', file_get_contents($this->root.'/file.txt'));
    }

    public function testResolvingAnUnfakedDiskThrows()
    {
        Storage::preventStrayDiskUsage();

        $this->expectExceptionObject(new StrayDiskUsageException('temp'));

        Storage::disk('temp');
    }

    public function testUsingAnUnfakedDiskDoesNotTouchTheFilesystem()
    {
        Storage::preventStrayDiskUsage();

        try {
            Storage::disk('temp')->put('file.txt', 'contents');
        } catch (StrayDiskUsageException) {
            //
        }

        $this->assertDirectoryDoesNotExist($this->root);
    }

    public function testReadingFromAnUnfakedDiskThrows()
    {
        Storage::preventStrayDiskUsage();

        $this->expectException(StrayDiskUsageException::class);

        Storage::disk('temp')->get('file.txt');
    }

    public function testTheDefaultDiskIsGuardedThroughTheFacade()
    {
        Storage::preventStrayDiskUsage();

        $this->expectExceptionObject(new StrayDiskUsageException('local'));

        Storage::put('file.txt', 'contents');
    }

    public function testTheCloudDiskIsGuarded()
    {
        Storage::preventStrayDiskUsage();

        $this->expectExceptionObject(new StrayDiskUsageException('s3'));

        Storage::cloud();
    }

    public function testResolvingADiskThroughTheContainerThrows()
    {
        Storage::preventStrayDiskUsage();

        $this->expectException(StrayDiskUsageException::class);

        $this->app->make('filesystem.disk');
    }

    public function testUsingAFakedDiskIsAllowed()
    {
        Storage::preventStrayDiskUsage();
        $fake = Storage::fake('temp');

        Storage::disk('temp')->put('file.txt', 'contents');

        $this->assertSame('contents', Storage::disk('temp')->get('file.txt'));
        $this->assertFileExists($fake->path('file.txt'));
        $this->assertFileDoesNotExist($this->root.'/file.txt');
    }

    public function testUsingAPersistentlyFakedDiskIsAllowed()
    {
        Storage::preventStrayDiskUsage();
        $fake = Storage::persistentFake('temp');

        Storage::disk('temp')->put('file.txt', 'contents');

        $this->assertFileExists($fake->path('file.txt'));
        $this->assertFileDoesNotExist($this->root.'/file.txt');
    }

    public function testFakingOneDiskLeavesTheOtherDisksGuarded()
    {
        Storage::preventStrayDiskUsage();
        Storage::fake('public');

        Storage::disk('public')->put('file.txt', 'contents');

        $this->expectExceptionObject(new StrayDiskUsageException('temp'));

        Storage::disk('temp');
    }

    public function testOnDemandDisksAreNotGuarded()
    {
        Storage::preventStrayDiskUsage();

        Storage::build(['driver' => 'local', 'root' => $this->root])->put('file.txt', 'contents');

        $this->assertSame('contents', file_get_contents($this->root.'/file.txt'));
    }
}
