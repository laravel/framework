<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Encryption\Encrypter;
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
        $this->filesystem->shouldReceive('put')
            ->andReturn(true);
        File::swap($this->filesystem);
    }

    public function testItFailsWhenFilenameIsNotProvided()
    {
        $this->artisan('file:decrypt')
            ->expectsQuestion('What is the filename to decrypt?', '')
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

        $this->artisan('file:decrypt', ['--cipher' => 'invalid', '--key' => 'abcdefghijklmnop'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
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

        $this->artisan('file:decrypt', ['--key' => 'invalid', '--cipher' => 'aes-128-cbc'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
            ->expectsOutputToContain('incorrect key length')
            ->assertExitCode(1);
    }

    public function testItFailsWhenEncryptionFileCannotBeFound()
    {
        $this->filesystem->shouldReceive('exists')->andReturn(true);

        $this->artisan('file:decrypt', ['--key' => 'secret-key'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
            ->expectsOutputToContain('File already exists.')
            ->assertExitCode(1);
    }

    public function testItFailsWhenFileExists()
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('file:decrypt', ['--key' => 'secret-key'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
            ->expectsOutputToContain('Encrypted file not found.')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheFileWithGeneratedKey()
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
                    ->encrypt('//registry.npmjs.org/:_authToken=123_ABC')
            );

        $this->artisan('file:decrypt', ['--force' => true, '--key' => 'base64:'.base64_encode($key)])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
            ->expectsOutputToContain('File successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.npmrc'), '//registry.npmjs.org/:_authToken=123_ABC');
    }

    public function testItGeneratesTheFileWithUserProvidedKey()
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
                    ->encrypt('//registry.npmjs.org/:_authToken=ABC_123')
            );

        $this->artisan('file:decrypt', ['--cipher' => 'aes-128-gcm', '--key' => 'abcdefghijklmnop'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
            ->expectsOutputToContain('File successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.npmrc'), '//registry.npmjs.org/:_authToken=ABC_123');
    }

    public function testItGeneratesTheFileWithKeyFromEnvironment()
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
                    ->encrypt('//registry.npmjs.org/:_authToken=1A_2B_3C')
            );

        $this->artisan('file:decrypt')
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
            ->expectsOutputToContain('File successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.npmrc'), '//registry.npmjs.org/:_authToken=1A_2B_3C');

        unset($_SERVER['LARAVEL_ENV_ENCRYPTION_KEY']);
    }

    public function testItGeneratesTheFileWhenForcing()
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
                    ->encrypt('//registry.npmjs.org/:_authToken=ABC_123')
            );

        $this->artisan('file:decrypt', ['--force' => true, '--key' => 'abcdefghijklmnop', '--cipher' => 'aes-128-gcm'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
            ->expectsOutputToContain('File successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.npmrc'), '//registry.npmjs.org/:_authToken=ABC_123');
    }

    public function testItDecryptsMultiLineFileCorrectly()
    {
        $contents = <<<'Text'
        //registry.npmjs.org/:_authToken=ABC_123
        //registry.npmjs.org/:_authToken=123_ABC
        //registry.npmjs.org/:_authToken=1A_2B_3C
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

        $this->artisan('file:decrypt', ['--force' => true, '--key' => 'abcdefghijklmnop', '--cipher' => 'aes-128-gcm'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
            ->expectsOutputToContain('File successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.npmrc'), $contents);
    }

    public function testItWritesTheFileCustomPath()
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
                    ->encrypt('//registry.npmjs.org/:_authToken=123_ABC')
            );

        $this->artisan('file:decrypt', ['--key' => 'abcdefghijklmnopabcdefghijklmnop', '--path' => '/tmp'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.production.encrypted')
            ->expectsOutputToContain('File successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with('/tmp'.DIRECTORY_SEPARATOR.'.npmrc.production', '//registry.npmjs.org/:_authToken=123_ABC');
    }

    public function testItCannotOverwriteEncryptedFiles()
    {
        $this->artisan('file:decrypt', ['--key' => 'abcdefghijklmnop'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.production')
            ->expectsOutputToContain('Invalid filename.')
            ->assertExitCode(1);

        $this->artisan('file:decrypt', ['--key' => 'abcdefghijklmnop'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.staging')
            ->expectsOutputToContain('Invalid filename.')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheFileWithInteractivelyUserProvidedKey()
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
                    ->encrypt('//registry.npmjs.org/:_authToken=B2_C3_A1')
            );

        $this->artisan('file:decrypt', ['--cipher' => 'aes-128-gcm'])
            ->expectsQuestion('What is the filename to decrypt?', '.npmrc.encrypted')
            ->expectsQuestion('What is the decryption key?', $key)
            ->expectsOutputToContain('File successfully decrypted.')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.npmrc'), '//registry.npmjs.org/:_authToken=B2_C3_A1');
    }
}
