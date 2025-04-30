<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Encryption\Encrypter;
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
        $this->filesystem->shouldReceive('get')
            ->andReturn(true)
            ->shouldReceive('put')
            ->andReturn('APP_NAME=Laravel');
        File::swap($this->filesystem);
    }

    public function testItFailsWhenFilenameIsNotProvided()
    {
        $this->artisan('file:encrypt')
            ->expectsQuestion('What is the filename to encrypt?', '')
            ->expectsOutputToContain('A filename is required.')
            ->assertExitCode(1);
    }

    public function testItFailsWithInvalidCipherFails()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('file:encrypt', ['--cipher' => 'invalid'])
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('Unsupported cipher')
            ->assertExitCode(1);
    }

    public function testItFailsUsingCipherWithInvalidKey()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('file:encrypt', ['--key' => 'invalid', '--cipher' => 'aes-128-cbc'])
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsOutputToContain('incorrect key length')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheCorrectFile()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('file:encrypt')
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.npmrc.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.npmrc.encrypted'), m::any());
    }

    public function testItFailsWhenFileCannotBeFound()
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('file:encrypt')
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('File not found.')
            ->assertExitCode(1);
    }

    public function testItFailsWhenEncryptionFileExists()
    {
        $this->filesystem->shouldReceive('exists')->andReturn(true);

        $this->artisan('file:encrypt')
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('Encrypted file already exists.')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheEncryptionFileWhenForcing()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $this->artisan('file:encrypt', ['--force' => true])
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.npmrc.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.npmrc.encrypted'), m::any());
    }

    public function testItEncryptsWithGivenKeyAndDisplaysIt()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('file:encrypt', ['--key' => $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP'])
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsOutputToContain('File successfully encrypted')
            ->expectsOutputToContain('.npmrc.encrypted')
            ->expectsOutputToContain($key)
            ->assertExitCode(0);
    }

    public function testItEncryptsWithGivenGeneratedBase64KeyAndDisplaysIt()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $key = Encrypter::generateKey('AES-256-CBC');

        $this->artisan('file:encrypt', ['--key' => 'base64:'.base64_encode($key)])
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsOutputToContain('File successfully encrypted')
            ->expectsOutputToContain('base64:'.base64_encode($key))
            ->expectsOutputToContain('.npmrc.encrypted')
            ->assertExitCode(0);
    }

    public function testItCanRemoveTheOriginalFile()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('file:encrypt', ['--prune' => true])
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.npmrc.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.npmrc.encrypted'), m::any());

        $this->filesystem->shouldHaveReceived('delete')
            ->with(base_path('.npmrc'));
    }

    public function testItEncryptsWithInteractivelyGivenKeyAndDisplaysIt()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('file:encrypt')
            ->expectsQuestion('What is the filename to encrypt?', '.npmrc')
            ->expectsQuestion('What encryption key would you like to use?', 'ask')
            ->expectsQuestion('What is the encryption key?', $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP')
            ->expectsOutputToContain('File successfully encrypted')
            ->expectsOutputToContain('.npmrc.encrypted')
            ->expectsOutputToContain($key)
            ->assertExitCode(0);
    }
}
