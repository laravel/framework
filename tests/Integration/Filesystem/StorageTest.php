<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use Symfony\Component\Process\Process;

#[RequiresOperatingSystem('Linux|DAR')]
class StorageTest extends TestCase
{
    protected $stubFile;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            File::put($file = storage_path('app/public/StardewTaylor.png'), File::get(__DIR__.'/Fixtures/StardewTaylor.png'));
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
        Storage::disk('public')->assertExists('StardewTaylor.png');
        $this->assertTrue(Storage::disk('public')->exists('StardewTaylor.png'));

        Storage::disk('public')->delete('StardewTaylor.png');

        Storage::disk('public')->assertMissing('StardewTaylor.png');
        $this->assertFalse(Storage::disk('public')->exists('StardewTaylor.png'));
    }

    public function testItCanDeleteViaFilesystemShouldUpdatesStorage()
    {
        Storage::disk('public')->assertExists('StardewTaylor.png');
        $this->assertTrue(Storage::disk('public')->exists('StardewTaylor.png'));

        File::delete($this->stubFile);

        Storage::disk('public')->assertMissing('StardewTaylor.png');
        $this->assertFalse(Storage::disk('public')->exists('StardewTaylor.png'));
    }

    public function testItCanDeleteViaFilesystemRequiresManualClearStatCacheOnStorageFromDifferentProcess()
    {
        Storage::disk('public')->assertExists('StardewTaylor.png');
        $this->assertTrue(Storage::disk('public')->exists('StardewTaylor.png'));

        Process::fromShellCommandline("rm {$this->stubFile}")->run();

        clearstatcache(true, $this->stubFile);
        Storage::disk('public')->assertMissing('StardewTaylor.png');
        $this->assertFalse(Storage::disk('public')->exists('StardewTaylor.png'));
    }

    public function testConditionable()
    {
        Storage::disk('public')->assertExists('StardewTaylor.png');
        $this->assertTrue(Storage::disk('public')->exists('StardewTaylor.png'));

        Storage::disk('public')->when(false)->delete('StardewTaylor.png');

        Storage::disk('public')->assertExists('StardewTaylor.png');
        $this->assertTrue(Storage::disk('public')->exists('StardewTaylor.png'));

        Storage::disk('public')->when(true)->delete('StardewTaylor.png');

        Storage::disk('public')->assertMissing('StardewTaylor.png');
        $this->assertFalse(Storage::disk('public')->exists('StardewTaylor.png'));
    }

    public function testItCanDeleteDirectoryViaStorage()
    {
        if (! Storage::disk('public')->exists('testdir')) {
            Storage::disk('public')->makeDirectory('testdir');
        }

        Storage::disk('public')->assertExists('testdir');
        $this->assertTrue(Storage::disk('public')->exists('testdir'));

        Storage::disk('public')->deleteDirectory('testdir');

        Storage::disk('public')->assertMissing('testdir');
        $this->assertFalse(Storage::disk('public')->exists('testdir'));
    }
}
