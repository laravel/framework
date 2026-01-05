<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class EnvironmentEncryptCommandTest extends TestCase
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

    public function testItFailsWithInvalidCipherFails(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt', ['--cipher' => 'invalid'])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
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

        $this->artisan('env:encrypt', ['--cipher' => 'aes-128-cbc', '--key' => 'invalid'])
            ->expectsOutputToContain('incorrect key length')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheCorrectFileWhenUsingEnvironment(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt', ['--env' => 'production'])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.env.production.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.production.encrypted'), m::any());
    }

    public function testItGeneratesTheCorrectFileWhenNotUsingEnvironment(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get');

        $this->artisan('env:encrypt')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.encrypted'), m::any());
    }

    public function testItFailsWhenEnvironmentFileCannotBeFound(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('env:encrypt')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('Environment file not found.')
            ->assertExitCode(1);
    }

    public function testItFailsWhenEncryptionFileExists(): void
    {
        $this->filesystem->shouldReceive('exists')->andReturn(true);

        $this->artisan('env:encrypt')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('Encrypted environment file already exists.')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheEncryptionFileWhenForcing(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $this->artisan('env:encrypt', ['--force' => true])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.encrypted'), m::any());
    }

    public function testItEncryptsWithGivenKeyAndDisplaysIt(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt', ['--key' => $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP'])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->expectsOutputToContain($key)
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);
    }

    public function testItEncryptsWithGivenGeneratedBase64KeyAndDisplaysIt(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $key = Encrypter::generateKey('AES-256-CBC');

        $this->artisan('env:encrypt', ['--key' => 'base64:'.base64_encode($key)])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->expectsOutputToContain('base64:'.base64_encode($key))
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);
    }

    public function testItEncryptsInReadableFormat(): void
    {
        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(true);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env.encrypted'))
            ->once()
            ->andReturn(false);
        $filesystem->shouldReceive('get')
            ->with(base_path('.env'))
            ->once()
            ->andReturn("APP_NAME=Laravel\nAPP_ENV=local");
        $filesystem->shouldReceive('put')
            ->once()
            ->with(base_path('.env.encrypted'), m::on(function ($content) {
                $lines = explode("\n", rtrim($content));

                return count($lines) === 2
                    && str_starts_with($lines[0], 'APP_NAME=')
                    && str_starts_with($lines[1], 'APP_ENV=');
            }))
            ->andReturn(true);
        File::swap($filesystem);

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP'])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);
    }

    public function testItSkipsCommentsAndBlankLinesInReadableFormat(): void
    {
        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(true);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env.encrypted'))
            ->once()
            ->andReturn(false);
        $filesystem->shouldReceive('get')
            ->with(base_path('.env'))
            ->once()
            ->andReturn("# Comment\nAPP_NAME=Laravel\n\nAPP_ENV=local");
        $filesystem->shouldReceive('put')
            ->once()
            ->with(base_path('.env.encrypted'), m::on(function ($content) {
                $lines = explode("\n", rtrim($content));

                // Comments and blank lines are skipped
                return count($lines) === 2
                    && str_starts_with($lines[0], 'APP_NAME=')
                    && str_starts_with($lines[1], 'APP_ENV=');
            }))
            ->andReturn(true);
        File::swap($filesystem);

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP'])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);
    }

    public function testItEncryptsMultiLineValuesInReadableFormat(): void
    {
        $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP';
        $encrypter = new Encrypter($key, 'AES-256-CBC');

        $originalContent = <<<'ENV'
APP_TEST_1="line1
line2
line3"
APP_TEST_2="línea1
lìnea2
lïne3"
ENV;

        $encryptedOutput = null;

        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(true);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env.encrypted'))
            ->once()
            ->andReturn(false);
        $filesystem->shouldReceive('get')
            ->with(base_path('.env'))
            ->once()
            ->andReturn($originalContent);
        $filesystem->shouldReceive('put')
            ->once()
            ->with(base_path('.env.encrypted'), m::on(function ($content) use (&$encryptedOutput) {
                $encryptedOutput = $content;

                return true;
            }))
            ->andReturn(true);
        File::swap($filesystem);

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => $key])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        // Verify structure
        $lines = explode("\n", rtrim($encryptedOutput));
        $this->assertCount(2, $lines);
        $this->assertTrue(str_starts_with($lines[0], 'APP_TEST_1='));
        $this->assertTrue(str_starts_with($lines[1], 'APP_TEST_2='));

        // Round-trip: decrypt and verify original values
        $encryptedValue1 = substr($lines[0], strlen('APP_TEST_1='));
        $encryptedValue2 = substr($lines[1], strlen('APP_TEST_2='));

        // Quotes are preserved, accented characters are preserved
        $this->assertSame("\"line1\nline2\nline3\"", $encrypter->decryptString($encryptedValue1));
        $this->assertSame("\"línea1\nlìnea2\nlïne3\"", $encrypter->decryptString($encryptedValue2));
    }

    public function testItEncryptsVariableReferencesInReadableFormat(): void
    {
        $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP';
        $encrypter = new Encrypter($key, 'AES-256-CBC');

        $originalContent = <<<'ENV'
APP_TEST_1=${APP_TEST}
APP_TEST_2="${APP_TEST}"
ENV;

        $encryptedOutput = null;

        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(true);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env.encrypted'))
            ->once()
            ->andReturn(false);
        $filesystem->shouldReceive('get')
            ->with(base_path('.env'))
            ->once()
            ->andReturn($originalContent);
        $filesystem->shouldReceive('put')
            ->once()
            ->with(base_path('.env.encrypted'), m::on(function ($content) use (&$encryptedOutput) {
                $encryptedOutput = $content;

                return true;
            }))
            ->andReturn(true);
        File::swap($filesystem);

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => $key])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        // Verify structure
        $lines = explode("\n", rtrim($encryptedOutput));
        $this->assertCount(2, $lines);
        $this->assertTrue(str_starts_with($lines[0], 'APP_TEST_1='));
        $this->assertTrue(str_starts_with($lines[1], 'APP_TEST_2='));

        // Round-trip: decrypt and verify values preserve variable reference syntax
        $encryptedValue1 = substr($lines[0], strlen('APP_TEST_1='));
        $encryptedValue2 = substr($lines[1], strlen('APP_TEST_2='));

        // Unquoted value preserved as-is, quoted value includes quotes
        $this->assertSame('${APP_TEST}', $encrypter->decryptString($encryptedValue1));
        $this->assertSame('"${APP_TEST}"', $encrypter->decryptString($encryptedValue2));
    }

    public function testItSkipsInvalidEnvLinesInReadableFormat(): void
    {
        $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP';
        $encrypter = new Encrypter($key, 'AES-256-CBC');

        $originalContent = <<<'ENV'
APP_TEST_1=valid
APP_TEST_2
APP_TEST_3=also_valid
ENV;

        $encryptedOutput = null;

        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(true);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env.encrypted'))
            ->once()
            ->andReturn(false);
        $filesystem->shouldReceive('get')
            ->with(base_path('.env'))
            ->once()
            ->andReturn($originalContent);
        $filesystem->shouldReceive('put')
            ->once()
            ->with(base_path('.env.encrypted'), m::on(function ($content) use (&$encryptedOutput) {
                $encryptedOutput = $content;

                return true;
            }))
            ->andReturn(true);
        File::swap($filesystem);

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => $key])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        // Verify structure - invalid line (APP_TEST_2 without =) is skipped
        $lines = explode("\n", rtrim($encryptedOutput));
        $this->assertCount(2, $lines);
        $this->assertTrue(str_starts_with($lines[0], 'APP_TEST_1='));
        $this->assertTrue(str_starts_with($lines[1], 'APP_TEST_3='));

        // Round-trip: decrypt and verify values
        $encryptedValue1 = substr($lines[0], strlen('APP_TEST_1='));
        $encryptedValue3 = substr($lines[1], strlen('APP_TEST_3='));

        $this->assertSame('valid', $encrypter->decryptString($encryptedValue1));
        $this->assertSame('also_valid', $encrypter->decryptString($encryptedValue3));
    }

    public function testItEncryptsSpecialCharactersInReadableFormat(): void
    {
        $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP';
        $encrypter = new Encrypter($key, 'AES-256-CBC');

        $originalContent = <<<'ENV'
NAME_1="Máximus"
NAME_2=M'aximus
NAME_3="M'aximus Decimus Meridius"
ENV;

        $encryptedOutput = null;

        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env'))
            ->once()
            ->andReturn(true);
        $filesystem->shouldReceive('exists')
            ->with(base_path('.env.encrypted'))
            ->once()
            ->andReturn(false);
        $filesystem->shouldReceive('get')
            ->with(base_path('.env'))
            ->once()
            ->andReturn($originalContent);
        $filesystem->shouldReceive('put')
            ->once()
            ->with(base_path('.env.encrypted'), m::on(function ($content) use (&$encryptedOutput) {
                $encryptedOutput = $content;

                return true;
            }))
            ->andReturn(true);
        File::swap($filesystem);

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => $key])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        // Verify structure
        $lines = explode("\n", rtrim($encryptedOutput));
        $this->assertCount(3, $lines);
        $this->assertTrue(str_starts_with($lines[0], 'NAME_1='));
        $this->assertTrue(str_starts_with($lines[1], 'NAME_2='));
        $this->assertTrue(str_starts_with($lines[2], 'NAME_3='));

        // Round-trip: decrypt and verify special characters are preserved
        $encryptedValue1 = substr($lines[0], strlen('NAME_1='));
        $encryptedValue2 = substr($lines[1], strlen('NAME_2='));
        $encryptedValue3 = substr($lines[2], strlen('NAME_3='));

        // Quoted values include the quotes, unquoted values are as-is
        $this->assertSame('"Máximus"', $encrypter->decryptString($encryptedValue1));
        $this->assertSame("M'aximus", $encrypter->decryptString($encryptedValue2));
        $this->assertSame("\"M'aximus Decimus Meridius\"", $encrypter->decryptString($encryptedValue3));
    }

    public function testItCanRemoveTheOriginalFile(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt', ['--prune' => true])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.encrypted'), m::any());

        $this->filesystem->shouldHaveReceived('delete')
            ->with(base_path('.env'));
    }

    public function testItEncryptsWithInteractivelyGivenKeyAndDisplaysIt(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt')
            ->expectsQuestion('What encryption key would you like to use?', 'ask')
            ->expectsQuestion('What is the encryption key?', $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP')
            ->expectsOutputToContain('Environment successfully encrypted')
            ->expectsOutputToContain($key)
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);
    }
}
