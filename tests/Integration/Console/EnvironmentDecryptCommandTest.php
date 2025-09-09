<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class EnvironmentDecryptCommandTest extends TestCase
{
    protected $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = m::spy(Filesystem::class);
        $this->filesystem->shouldReceive('put')
            ->andReturn(true);
        File::swap($this->filesystem);
    }

    public function testItFailsWithInvalidCipherFails(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:decrypt', ['--cipher' => 'invalid', '--key' => 'abcdefghijklmnop'])
            ->expectsOutputToContain('Unsupported cipher')
            ->assertExitCode(1);
    }

    public function testItFailsUsingCipherWithInvalidKey(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:decrypt', ['--cipher' => 'aes-128-cbc', '--key' => 'invalid'])
            ->expectsOutputToContain('incorrect key length')
            ->assertExitCode(1);
    }

    public function testItFailsWhenEncryptionFileCannotBeFound(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(true);

        $this->artisan('env:decrypt', ['--key' => 'secret-key'])
            ->expectsOutputToContain('Environment file already exists.')
            ->assertExitCode(1);
    }

    public function testItFailsWhenEnvironmentFileExists(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('env:decrypt', ['--key' => 'secret-key'])
            ->expectsOutputToContain('Encrypted environment file not found.')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheEnvironmentFileWithGeneratedKey(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                (new Encrypter($key = Encrypter::generateKey('AES-256-CBC'), 'AES-256-CBC'))
                    ->encrypt('APP_NAME=Laravel')
            );

        $this->artisan('env:decrypt', ['--force' => true, '--key' => 'base64:'.base64_encode($key)])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env'), 'APP_NAME=Laravel');
    }

    public function testItGeneratesTheEnvironmentFileWithUserProvidedKey(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                (new Encrypter('abcdefghijklmnop', 'aes-128-gcm'))
                    ->encrypt('APP_NAME="Laravel Two"')
            );

        $this->artisan('env:decrypt', ['--cipher' => 'aes-128-gcm', '--key' => 'abcdefghijklmnop'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env'), 'APP_NAME="Laravel Two"');
    }

    public function testItGeneratesTheEnvironmentFileWithKeyFromEnvironment(): void
    {
        $_SERVER['LARAVEL_ENV_ENCRYPTION_KEY'] = 'ponmlkjihgfedcbaponmlkjihgfedcba';

        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                (new Encrypter('ponmlkjihgfedcbaponmlkjihgfedcba', 'AES-256-CBC'))
                    ->encrypt('APP_NAME="Laravel Three"')
            );

        $this->artisan('env:decrypt')
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env'), 'APP_NAME="Laravel Three"');

        unset($_SERVER['LARAVEL_ENV_ENCRYPTION_KEY']);
    }

    public function testItGeneratesTheEnvironmentFileWhenForcing(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                (new Encrypter('abcdefghijklmnop', 'aes-128-gcm'))
                    ->encrypt('APP_NAME="Laravel Two"')
            );

        $this->artisan('env:decrypt', ['--force' => true, '--key' => 'abcdefghijklmnop', '--cipher' => 'aes-128-gcm'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env'), 'APP_NAME="Laravel Two"');
    }

    public function testItDecryptsMultiLineEnvironmentCorrectly(): void
    {
        $contents = <<<'Text'
        APP_NAME=Laravel
        APP_ENV=local
        APP_DEBUG=true
        APP_URL=http://localhost

        LOG_CHANNEL=stack
        LOG_DEPRECATIONS_CHANNEL=null
        LOG_LEVEL=debug

        DB_CONNECTION=mysql
        DB_HOST=127.0.0.1
        DB_PORT=3306
        DB_DATABASE=laravel
        DB_USERNAME=root
        DB_PASSWORD=
        Text;

        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                (new Encrypter('abcdefghijklmnop', 'aes-128-gcm'))
                    ->encrypt($contents)
            );

        $this->artisan('env:decrypt', ['--force' => true, '--key' => 'abcdefghijklmnop', '--cipher' => 'aes-128-gcm'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env'), $contents);
    }

    public function testItWritesTheEnvironmentFileCustomFilename(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                (new Encrypter('abcdefghijklmnopabcdefghijklmnop', 'AES-256-CBC'))
                    ->encrypt('APP_NAME="Laravel Two"')
            );

        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnopabcdefghijklmnop', '--filename' => '.env'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env'), 'APP_NAME="Laravel Two"');
    }

    public function testItWritesTheEnvironmentFileCustomPath(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                (new Encrypter('abcdefghijklmnopabcdefghijklmnop', 'AES-256-CBC'))
                    ->encrypt('APP_NAME="Laravel Two"')
            );

        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnopabcdefghijklmnop', '--path' => '/tmp'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with('/tmp'.DIRECTORY_SEPARATOR.'.env.production', 'APP_NAME="Laravel Two"');
    }

    public function testItWritesTheEnvironmentFileCustomPathAndFilename(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                (new Encrypter('abcdefghijklmnopabcdefghijklmnop', 'AES-256-CBC'))
                    ->encrypt('APP_NAME="Laravel Two"')
            );

        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnopabcdefghijklmnop', '--filename' => '.env', '--path' => '/tmp'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with('/tmp'.DIRECTORY_SEPARATOR.'.env', 'APP_NAME="Laravel Two"');
    }

    public function testItCannotOverwriteEncryptedFiles(): void
    {
        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnop', '--filename' => '.env.production.encrypted'])
            ->expectsOutputToContain('Invalid filename.')
            ->assertExitCode(1);

        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnop', '--filename' => '.env.staging.encrypted'])
            ->expectsOutputToContain('Invalid filename.')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheEnvironmentFileWithInteractivelyUserProvidedKey(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                (new Encrypter($key = 'abcdefghijklmnop', 'aes-128-gcm'))
                    ->encrypt('APP_NAME="Laravel Two"')
            );

        $this->artisan('env:decrypt', ['--cipher' => 'aes-128-gcm'])
            ->expectsQuestion('What is the decryption key?', $key)
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env'), 'APP_NAME="Laravel Two"');
    }
}
