<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\File;
use Illuminate\Tests\Integration\Console\Fixtures\ArrayFilesystem;
use Orchestra\Testbench\TestCase;

class EnvironmentDecryptCommandTest extends TestCase
{
    protected $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new ArrayFilesystem;

        File::swap($this->filesystem);
    }

    public function testItFailsWithInvalidCipherFails(): void
    {
        $this->filesystem->files[base_path('.env.encrypted')] = 'encrypted';

        $this->artisan('env:decrypt', ['--cipher' => 'invalid', '--key' => 'abcdefghijklmnop'])
            ->expectsOutputToContain('Unsupported cipher')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);
    }

    public function testItFailsUsingCipherWithInvalidKey(): void
    {
        $this->filesystem->files[base_path('.env.encrypted')] = 'encrypted';

        $this->artisan('env:decrypt', ['--cipher' => 'aes-128-cbc', '--key' => 'invalid'])
            ->expectsOutputToContain('incorrect key length')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);
    }

    public function testItFailsWhenEncryptionFileCannotBeFound(): void
    {
        $this->filesystem->files[base_path('.env.encrypted')] = 'encrypted';
        $this->filesystem->files[base_path('.env')] = 'existing';

        $this->artisan('env:decrypt', ['--key' => 'secret-key'])
            ->expectsOutputToContain('Environment file already exists.')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);
    }

    public function testItFailsWhenEnvironmentFileExists(): void
    {
        $this->artisan('env:decrypt', ['--key' => 'secret-key'])
            ->expectsOutputToContain('Encrypted environment file not found.')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);
    }

    public function testItGeneratesTheEnvironmentFileWithGeneratedKey(): void
    {
        $this->filesystem->files[base_path('.env.encrypted')] = (new Encrypter($key = Encrypter::generateKey('AES-256-CBC'), 'AES-256-CBC'))
            ->encrypt('APP_NAME=Laravel');
        $this->filesystem->files[base_path('.env')] = 'existing';

        $this->artisan('env:decrypt', ['--force' => true, '--key' => 'base64:'.base64_encode($key)])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame('APP_NAME=Laravel', $this->filesystem->files[base_path('.env')]);
    }

    public function testItGeneratesTheEnvironmentFileWithUserProvidedKey(): void
    {
        $this->filesystem->files[base_path('.env.encrypted')] = (new Encrypter('abcdefghijklmnop', 'aes-128-gcm'))
            ->encrypt('APP_NAME="Laravel Two"');

        $this->artisan('env:decrypt', ['--cipher' => 'aes-128-gcm', '--key' => 'abcdefghijklmnop'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame('APP_NAME="Laravel Two"', $this->filesystem->files[base_path('.env')]);
    }

    public function testItGeneratesTheEnvironmentFileWithKeyFromEnvironment(): void
    {
        $_SERVER['LARAVEL_ENV_ENCRYPTION_KEY'] = 'ponmlkjihgfedcbaponmlkjihgfedcba';

        $this->filesystem->files[base_path('.env.encrypted')] = (new Encrypter('ponmlkjihgfedcbaponmlkjihgfedcba', 'AES-256-CBC'))
            ->encrypt('APP_NAME="Laravel Three"');

        $this->artisan('env:decrypt')
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame('APP_NAME="Laravel Three"', $this->filesystem->files[base_path('.env')]);

        unset($_SERVER['LARAVEL_ENV_ENCRYPTION_KEY']);
    }

    public function testItGeneratesTheEnvironmentFileWhenForcing(): void
    {
        $this->filesystem->files[base_path('.env.encrypted')] = (new Encrypter('abcdefghijklmnop', 'aes-128-gcm'))
            ->encrypt('APP_NAME="Laravel Two"');
        $this->filesystem->files[base_path('.env')] = 'existing';

        $this->artisan('env:decrypt', ['--force' => true, '--key' => 'abcdefghijklmnop', '--cipher' => 'aes-128-gcm'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame('APP_NAME="Laravel Two"', $this->filesystem->files[base_path('.env')]);
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

        $this->filesystem->files[base_path('.env.encrypted')] = (new Encrypter('abcdefghijklmnop', 'aes-128-gcm'))
            ->encrypt($contents);
        $this->filesystem->files[base_path('.env')] = 'existing';

        $this->artisan('env:decrypt', ['--force' => true, '--key' => 'abcdefghijklmnop', '--cipher' => 'aes-128-gcm'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame($contents, $this->filesystem->files[base_path('.env')]);
    }

    public function testItWritesTheEnvironmentFileCustomFilename(): void
    {
        $this->filesystem->files[base_path('.env.production.encrypted')] = (new Encrypter('abcdefghijklmnopabcdefghijklmnop', 'AES-256-CBC'))
            ->encrypt('APP_NAME="Laravel Two"');

        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnopabcdefghijklmnop', '--filename' => '.env'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame('APP_NAME="Laravel Two"', $this->filesystem->files[base_path('.env')]);
    }

    public function testItWritesTheEnvironmentFileCustomPath(): void
    {
        $this->filesystem->files[base_path('.env.production.encrypted')] = (new Encrypter('abcdefghijklmnopabcdefghijklmnop', 'AES-256-CBC'))
            ->encrypt('APP_NAME="Laravel Two"');

        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnopabcdefghijklmnop', '--path' => '/tmp'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame('APP_NAME="Laravel Two"', $this->filesystem->files['/tmp'.DIRECTORY_SEPARATOR.'.env.production']);
    }

    public function testItWritesTheEnvironmentFileCustomPathAndFilename(): void
    {
        $this->filesystem->files[base_path('.env.production.encrypted')] = (new Encrypter('abcdefghijklmnopabcdefghijklmnop', 'AES-256-CBC'))
            ->encrypt('APP_NAME="Laravel Two"');

        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnopabcdefghijklmnop', '--filename' => '.env', '--path' => '/tmp'])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame('APP_NAME="Laravel Two"', $this->filesystem->files['/tmp'.DIRECTORY_SEPARATOR.'.env']);
    }

    public function testItCannotOverwriteEncryptedFiles(): void
    {
        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnop', '--filename' => '.env.production.encrypted'])
            ->expectsOutputToContain('Invalid filename.')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);

        $this->artisan('env:decrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnop', '--filename' => '.env.staging.encrypted'])
            ->expectsOutputToContain('Invalid filename.')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);
    }

    public function testItGeneratesTheEnvironmentFileWithInteractivelyUserProvidedKey(): void
    {
        $this->filesystem->files[base_path('.env.encrypted')] = (new Encrypter($key = 'abcdefghijklmnop', 'aes-128-gcm'))
            ->encrypt('APP_NAME="Laravel Two"');

        $this->artisan('env:decrypt', ['--cipher' => 'aes-128-gcm'])
            ->expectsQuestion('What is the decryption key?', $key)
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame('APP_NAME="Laravel Two"', $this->filesystem->files[base_path('.env')]);
    }

    public function testItAutoDetectsAndDecryptsReadableFormat(): void
    {
        $key = 'abcdefghijklmnopabcdefghijklmnop';
        $encrypter = new Encrypter($key, 'AES-256-CBC');

        // Create readable format encrypted content
        $encryptedContent = 'APP_NAME='.$encrypter->encryptString('Laravel')."\n".
                           'APP_ENV='.$encrypter->encryptString('local');

        $this->filesystem->files[base_path('.env.encrypted')] = $encryptedContent;

        $this->artisan('env:decrypt', ['--key' => $key])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame("APP_NAME=Laravel\nAPP_ENV=local\n", $this->filesystem->files[base_path('.env')]);
    }

    public function testItStillDecryptsBlobFormat(): void
    {
        $key = 'abcdefghijklmnopabcdefghijklmnop';
        $encrypter = new Encrypter($key, 'AES-256-CBC');

        // Create blob format (entire file encrypted as one)
        $originalContent = "APP_NAME=Laravel\nAPP_ENV=local";
        $encryptedContent = $encrypter->encrypt($originalContent);

        $this->filesystem->files[base_path('.env.encrypted')] = $encryptedContent;

        $this->artisan('env:decrypt', ['--key' => $key])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame($originalContent, $this->filesystem->files[base_path('.env')]);
    }

    public function testItDecryptsBlobFormatWithNewlineInContent(): void
    {
        $key = 'abcdefghijklmnopabcdefghijklmnop';
        $encrypter = new Encrypter($key, 'AES-256-CBC');

        // Create blob format and inject a newline (simulating wrapped base64)
        $originalContent = "APP_NAME=Laravel\nAPP_ENV=local";
        $encryptedContent = $encrypter->encrypt($originalContent);

        // Insert a newline in the middle of the base64 string
        $midpoint = (int) (strlen($encryptedContent) / 2);
        $encryptedContentWithNewline = substr($encryptedContent, 0, $midpoint)."\n".substr($encryptedContent, $midpoint);

        $this->filesystem->files[base_path('.env.encrypted')] = $encryptedContentWithNewline;

        $this->artisan('env:decrypt', ['--key' => $key])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame($originalContent, $this->filesystem->files[base_path('.env')]);
    }

    public function testItDecryptsReadableFormatWithBase64Values(): void
    {
        $key = 'abcdefghijklmnopabcdefghijklmnop';
        $encrypter = new Encrypter($key, 'AES-256-CBC');

        // Create readable format with base64 value containing = signs
        $encryptedContent = 'APP_KEY='.$encrypter->encryptString('base64:Ge+W23u+VZI2tbrp5QCGWrsUuxgcD65i7jtTRR2ZqfY=')."\n".
                           'APP_ENV='.$encrypter->encryptString('local');

        $this->filesystem->files[base_path('.env.encrypted')] = $encryptedContent;

        $this->artisan('env:decrypt', ['--key' => $key])
            ->expectsOutputToContain('Environment successfully decrypted.')
            ->assertExitCode(0);

        $this->assertSame("APP_KEY=base64:Ge+W23u+VZI2tbrp5QCGWrsUuxgcD65i7jtTRR2ZqfY=\nAPP_ENV=local\n", $this->filesystem->files[base_path('.env')]);
    }
}
