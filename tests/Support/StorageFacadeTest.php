<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToReadFile;
use Orchestra\Testbench\TestCase;

class StorageFacadeTest extends TestCase
{
    public function testFake_whenDiskNotConfigured_doesNotThrowExceptionOnError()
    {
        $result = Storage::fake('test')->get('nonExistentFile');

        $this->assertNull($result);
    }

    public function testFake_whenThrowSetToDisk_throwsExceptionOnError()
    {
        Config::set('filesystems.disks.test', ['throw' => true]);

        $this->expectException(UnableToReadFile::class);
        Storage::fake('test')->get('nonExistentFile');
    }

    public function testFake_whenThrowOverwritten_usesOverwrite()
    {
        Config::set('filesystems.disks.test', ['throw' => true]);

        $result = Storage::fake('test', ['throw' => false])->get('nonExistentFile');
        $this->assertNull($result);
    }

    public function testPersistentFake_whenDiskNotConfigured_doesNotThrowExceptionOnError()
    {
        $result = Storage::persistentFake('test')->get('nonExistentFile');

        $this->assertNull($result);
    }

    public function testPersistentFake_whenThrowSetToDisk_throwsExceptionOnError()
    {
        Config::set('filesystems.disks.test', ['throw' => true]);

        $this->expectException(UnableToReadFile::class);
        Storage::persistentFake('test')->get('nonExistentFile');
    }

    public function testPersistentFake_whenThrowOverwritten_usesOverwrite()
    {
        Config::set('filesystems.disks.test', ['throw' => true]);

        $result = Storage::persistentFake('test', ['throw' => false])->get('nonExistentFile');
        $this->assertNull($result);
    }
}
