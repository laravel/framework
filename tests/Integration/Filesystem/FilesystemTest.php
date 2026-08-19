<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use Symfony\Component\Process\Process;

#[RequiresOperatingSystem('Linux|Darwin')]
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

    public function testItCanDeleteDirectoryViaFilesystem()
    {
        if (! File::exists(storage_path('app/public/testdir'))) {
            File::makeDirectory(storage_path('app/public/testdir'));
        }

        $this->assertTrue(File::exists(storage_path('app/public/testdir')));

        File::deleteDirectory(storage_path('app/public/testdir'));

        $this->assertFalse(File::exists(storage_path('app/public/testdir')));
    }

    public function testSharedGetReadsEntireStreamEvenWhenReadIsPartial(): void
    {
        if (! in_array('partialread', stream_get_wrappers(), true)) {
            stream_wrapper_register('partialread', PartialReadStreamWrapper::class);
        }

        $payload = str_repeat('a', 10 * 1024); // 10 KiB
        PartialReadStreamWrapper::setData($payload);

        $fs = new Filesystem();

        $contents = $fs->sharedGet('partialread://test');

        $this->assertSame(strlen($payload), strlen($contents));
        $this->assertSame($payload, $contents);
    }

    protected function tearDown(): void
    {
        if (in_array('partialread', stream_get_wrappers(), true)) {
            @stream_wrapper_unregister('partialread');
        }

        parent::tearDown();
    }
}

class PartialReadStreamWrapper
{
    public $context;

    private static string $data = '';
    private int $pos = 0;

    private static bool $locked = false;

    public static function setData(string $data): void
    {
        self::$data = $data;
    }

    public function stream_open($path, $mode, $options, &$opened_path): bool
    {
        $this->pos = 0;

        return true;
    }

    public function stream_read($count): string
    {
        // We emulate the behavior: one read returns a maximum of 8 KiB.
        $count = min($count, 8192);

        $chunk = substr(self::$data, $this->pos, $count);
        $this->pos += strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->pos >= strlen(self::$data);
    }

    public function stream_stat(): array
    {
        return ['size' => strlen(self::$data)];
    }

    public function url_stat($path, $flags): array
    {
        return ['size' => strlen(self::$data)];
    }

    /**
     * Required for flock() support on user-space streams.
     *
     * @param  int  $operation  One of LOCK_SH, LOCK_EX, LOCK_UN (+ optional LOCK_NB)
     * @return bool
     */
    public function stream_lock($operation): bool
    {
        // For testing purposes, a “successful” implementation is sufficient.
        // LOCK_UN — remove, otherwise assume that it is locked.
        if (($operation & LOCK_UN) === LOCK_UN) {
            self::$locked = false;

            return true;
        }

        // Optionally, LOCK_NB can be taken into account, but it is not required here.
        self::$locked = true;

        return true;
    }
}
