<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Process\Process;

/**
 * @requires OS Linux|Darwin
 */
class FilesystemTest extends TestCase
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

    public function testItCanDeleteViaFilesystemShouldUpdatesFileExists()
    {
        $this->assertTrue(File::exists($this->stubFile));
        $this->assertTrue(File::isFile($this->stubFile));

        File::delete($this->stubFile);

        $this->assertFalse(File::exists($this->stubFile));
    }

    public function testItCanDeleteViaFilesystemRequiresManualClearStatCacheOnFileExistsFromDifferentProcess()
    {
        $this->assertTrue(File::exists($this->stubFile));
        $this->assertTrue(File::isFile($this->stubFile));

        Process::fromShellCommandline("rm {$this->stubFile}")->run();

        clearstatcache(true, $this->stubFile);
        $this->assertFalse(File::exists($this->stubFile));
    }

    public function testItCanDeleteViaFilesystemShouldUpdatesIsFile()
    {
        $this->assertTrue(File::exists($this->stubFile));
        $this->assertTrue(File::isFile($this->stubFile));

        File::delete($this->stubFile);

        $this->assertFalse(File::isFile($this->stubFile));
    }

    public function testItCanDeleteViaFilesystemRequiresManualClearStatCacheOnIsFileFromDifferentProcess()
    {
        $this->assertTrue(File::exists($this->stubFile));
        $this->assertTrue(File::isFile($this->stubFile));

        Process::fromShellCommandline("rm {$this->stubFile}")->run();

        clearstatcache(true, $this->stubFile);
        $this->assertFalse(File::isFile($this->stubFile));
    }
}
