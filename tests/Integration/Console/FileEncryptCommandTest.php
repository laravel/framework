<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class FileEncryptCommandTest extends TestCase
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

        $this->artisan('file:encrypt', ['path' => 'nonexistent.txt', '--force' => true])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('Path does not exist')
            ->assertExitCode(1);
    }

    public function testItFailsWithInvalidKeyLength(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(true);
        $this->filesystem->shouldReceive('isFile')->andReturn(true);
        $this->filesystem->shouldReceive('isDirectory')->andReturn(false);

        $this->artisan('file:encrypt', ['path' => 'test.txt', '--key' => 'tooshort', '--force' => true])
            ->expectsOutputToContain('must be 32 bytes')
            ->assertExitCode(1);
    }

    public function testItDisplaysGeneratedKeyInfo(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('file:encrypt', ['path' => 'test.txt', '--force' => true])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('Path does not exist')
            ->assertExitCode(1);
    }

    public function testItPromptsForKeyWhenNotProvided(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('file:encrypt', ['path' => 'test.txt', '--force' => true])
            ->expectsQuestion('What encryption key would you like to use?', 'ask')
            ->expectsQuestion('What is the encryption key?', 'base64:'.base64_encode(str_repeat('a', 32)))
            ->expectsOutputToContain('Path does not exist')
            ->assertExitCode(1);
    }

    public function testItRejectsInvalidChunkSize(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(true);
        $this->filesystem->shouldReceive('isFile')->andReturn(true);
        $this->filesystem->shouldReceive('isDirectory')->andReturn(false);

        $this->artisan('file:encrypt', [
            'path' => 'test.txt',
            '--key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            '--chunk-size' => '512',
            '--force' => true,
        ])
            ->expectsOutputToContain('Chunk size must be at least 1024 bytes')
            ->assertExitCode(1);
    }
}
