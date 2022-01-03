<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class StorageTest extends TestCase
{
    protected $stubFile;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            File::put($file = storage_path('app/public/StarDewTaylor.png'), File::get(__DIR__.'/Fixtures/StarDewTaylor.png'));
            $this->stubFile = $file;
        });

        $this->beforeApplicationDestroyed(function () {
            if (File::exists($this->stubFile)) {
                File::delete($this->stubFile);
            }
        });

        parent::setUp();
    }

    public function testItCanDeleteViaStorage()
    {
        $this->assertTrue(File::exists($this->stubFile));
        $this->assertTrue(File::isFile($this->stubFile));
        Storage::disk('public')->assertExists('StarDewTaylor.png');
        $this->assertTrue(Storage::disk('public')->exists('StarDewTaylor.png'));

        Storage::disk('public')->delete('StarDewTaylor.png');

        $this->assertFalse(File::exists($this->stubFile));
        $this->assertFalse(File::isFile($this->stubFile));
        Storage::disk('public')->assertMissing('StarDewTaylor.png');
        $this->assertFalse(Storage::disk('public')->exists('StarDewTaylor.png'));
    }

    public function testItCanDeleteViaFilesystemShouldUpdatesFileExists()
    {
        $this->assertTrue(File::exists($this->stubFile));
        $this->assertTrue(File::isFile($this->stubFile));
        Storage::disk('public')->assertExists('StarDewTaylor.png');
        $this->assertTrue(Storage::disk('public')->exists('StarDewTaylor.png'));

        File::delete('StarDewTaylor.png');

        $this->assertFalse(File::exists($this->stubFile));
    }

    public function testItCanDeleteViaFilesystemShouldUpdatesIsFile()
    {
        $this->assertTrue(File::exists($this->stubFile));
        $this->assertTrue(File::isFile($this->stubFile));
        Storage::disk('public')->assertExists('StarDewTaylor.png');
        $this->assertTrue(Storage::disk('public')->exists('StarDewTaylor.png'));

        File::delete('StarDewTaylor.png');

        $this->assertFalse(File::isFile($this->stubFile));
    }

    public function testItCanDeleteViaFilesystemShouldUpdatesStorage()
    {
        $this->assertTrue(File::exists($this->stubFile));
        $this->assertTrue(File::isFile($this->stubFile));
        Storage::disk('public')->assertExists('StarDewTaylor.png');
        $this->assertTrue(Storage::disk('public')->exists('StarDewTaylor.png'));

        File::delete('StarDewTaylor.png');

        Storage::disk('public')->assertMissing('StarDewTaylor.png');
        $this->assertFalse(Storage::disk('public')->exists('StarDewTaylor.png'));
    }
}
