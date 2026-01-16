<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class FileDecryptCommandTest extends TestCase
{
    protected $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = m::spy(Filesystem::class);
        File::swap($this->filesystem);
    }

    public function testItFailsWhenPathDoesNotExist(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('file:decrypt', ['path' => 'nonexistent.enc', '--force' => true, '--key' => 'base64:'.base64_encode(str_repeat('a', 32))])
            ->expectsOutputToContain('Path does not exist')
            ->assertExitCode(1);
    }

    public function testItFailsWithInvalidKeyLength(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(true);
        $this->filesystem->shouldReceive('isFile')->andReturn(true);
        $this->filesystem->shouldReceive('isDirectory')->andReturn(false);

        $this->artisan('file:decrypt', ['path' => 'test.enc', '--key' => 'tooshort', '--force' => true])
            ->expectsOutputToContain('must be 32 bytes')
            ->assertExitCode(1);
    }

    public function testItPromptsForKeyWhenNotProvided(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('file:decrypt', ['path' => 'test.enc', '--force' => true])
            ->expectsQuestion('What is the decryption key?', 'base64:'.base64_encode(str_repeat('a', 32)))
            ->expectsOutputToContain('Path does not exist')
            ->assertExitCode(1);
    }

    public function testItRequiresPathOrScanOption(): void
    {
        $this->artisan('file:decrypt', ['--key' => 'base64:'.base64_encode(str_repeat('a', 32)), '--force' => true])
            ->expectsOutputToContain('provide a path or use the --scan option')
            ->assertExitCode(1);
    }

    public function testScanFindsNoFilesWhenNoneExist(): void
    {
        $this->artisan('file:decrypt', ['--scan' => true, '--key' => 'base64:'.base64_encode(str_repeat('a', 32)), '--force' => true])
            ->expectsOutputToContain('No encrypted files found')
            ->assertExitCode(1);
    }
}
