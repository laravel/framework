<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\File;
use Illuminate\Tests\Integration\Console\Fixtures\InMemoryFilesystem;
use Orchestra\Testbench\TestCase;

class EnvironmentEncryptCommandTest extends TestCase
{
    protected $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new InMemoryFilesystem;
        $this->filesystem->files[base_path('.env')] = 'APP_NAME=Laravel';

        File::swap($this->filesystem);
    }

    public function testItFailsWithInvalidCipherFails(): void
    {
        $this->artisan('env:encrypt', ['--cipher' => 'invalid'])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('Unsupported cipher')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);
    }

    public function testItFailsUsingCipherWithInvalidKey(): void
    {
        $this->artisan('env:encrypt', ['--cipher' => 'aes-128-cbc', '--key' => 'invalid'])
            ->expectsOutputToContain('incorrect key length')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);
    }

    public function testItGeneratesTheCorrectFileWhenUsingEnvironment(): void
    {
        $this->filesystem->files[base_path('.env.production')] = 'APP_NAME=Laravel';

        $this->artisan('env:encrypt', ['--env' => 'production'])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.env.production.encrypted')
            ->assertExitCode(0);

        $this->assertSame([base_path('.env.production.encrypted')], $this->filesystem->writes);
    }

    public function testItGeneratesTheCorrectFileWhenNotUsingEnvironment(): void
    {
        $this->artisan('env:encrypt')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->assertSame([base_path('.env.encrypted')], $this->filesystem->writes);
        $this->assertSame([], $this->filesystem->deletes);
    }

    public function testItFailsWhenEnvironmentFileCannotBeFound(): void
    {
        unset($this->filesystem->files[base_path('.env')]);

        $this->artisan('env:encrypt')
            ->expectsOutputToContain('Environment file not found.')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);
    }

    public function testItFailsWhenEncryptionFileExists(): void
    {
        $this->filesystem->files[base_path('.env.encrypted')] = 'existing';

        $this->artisan('env:encrypt')
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('Encrypted environment file already exists.')
            ->assertExitCode(1);

        $this->assertSame([], $this->filesystem->writes);
        $this->assertSame('existing', $this->filesystem->files[base_path('.env.encrypted')]);
    }

    public function testItGeneratesTheEncryptionFileWhenForcing(): void
    {
        $this->filesystem->files[base_path('.env.encrypted')] = 'existing';

        $this->artisan('env:encrypt', ['--force' => true])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->assertSame([base_path('.env.encrypted')], $this->filesystem->writes);
        $this->assertNotSame('existing', $this->filesystem->files[base_path('.env.encrypted')]);
    }

    public function testItReencryptsReadableValuesWithANewKeyWhenForcing(): void
    {
        $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP';
        $oldEncrypter = new Encrypter(str_repeat('x', 32), 'AES-256-CBC');

        $this->filesystem->files[base_path('.env')] = 'DB_PASSWORD=1';
        $this->filesystem->files[base_path('.env.encrypted')] = 'DB_PASSWORD='.$oldEncrypter->encryptString('1')."\n";

        $this->artisan('env:encrypt', ['--readable' => true, '--force' => true, '--key' => $key])
            ->assertExitCode(0);

        $encryptedOutput = $this->filesystem->files[base_path('.env.encrypted')];

        $encrypter = new Encrypter($key, 'AES-256-CBC');
        $this->assertSame('1', $encrypter->decryptString(substr(rtrim($encryptedOutput), strlen('DB_PASSWORD='))));
    }

    public function testItEncryptsWithGivenKeyAndDisplaysIt(): void
    {
        $this->artisan('env:encrypt', ['--key' => $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP'])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->expectsOutputToContain($key)
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $encrypter = new Encrypter($key, 'AES-256-CBC');
        $this->assertSame('APP_NAME=Laravel', $encrypter->decrypt($this->filesystem->files[base_path('.env.encrypted')]));
    }

    public function testItEncryptsWithGivenGeneratedBase64KeyAndDisplaysIt(): void
    {
        $key = Encrypter::generateKey('AES-256-CBC');

        $this->artisan('env:encrypt', ['--key' => 'base64:'.base64_encode($key)])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->expectsOutputToContain('base64:'.base64_encode($key))
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $encrypter = new Encrypter($key, 'AES-256-CBC');
        $this->assertSame('APP_NAME=Laravel', $encrypter->decrypt($this->filesystem->files[base_path('.env.encrypted')]));
    }

    public function testItEncryptsInReadableFormat(): void
    {
        $this->filesystem->files[base_path('.env')] = "APP_NAME=Laravel\nAPP_ENV=local";

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP'])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        $lines = explode("\n", rtrim($this->filesystem->files[base_path('.env.encrypted')]));

        $this->assertCount(2, $lines);
        $this->assertTrue(str_starts_with($lines[0], 'APP_NAME='));
        $this->assertTrue(str_starts_with($lines[1], 'APP_ENV='));
    }

    public function testItSkipsCommentsAndBlankLinesInReadableFormat(): void
    {
        $this->filesystem->files[base_path('.env')] = "# Comment\nAPP_NAME=Laravel\n\nAPP_ENV=local";

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP'])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        $lines = explode("\n", rtrim($this->filesystem->files[base_path('.env.encrypted')]));

        // Comments and blank lines are skipped
        $this->assertCount(2, $lines);
        $this->assertTrue(str_starts_with($lines[0], 'APP_NAME='));
        $this->assertTrue(str_starts_with($lines[1], 'APP_ENV='));
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

        $this->filesystem->files[base_path('.env')] = $originalContent;

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => $key])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        $encryptedOutput = $this->filesystem->files[base_path('.env.encrypted')];

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

        $this->filesystem->files[base_path('.env')] = $originalContent;

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => $key])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        $encryptedOutput = $this->filesystem->files[base_path('.env.encrypted')];

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

        $this->filesystem->files[base_path('.env')] = $originalContent;

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => $key])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        $encryptedOutput = $this->filesystem->files[base_path('.env.encrypted')];

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

        $this->filesystem->files[base_path('.env')] = $originalContent;

        $this->artisan('env:encrypt', ['--readable' => true, '--key' => $key])
            ->expectsOutputToContain('Environment successfully encrypted')
            ->assertExitCode(0);

        $encryptedOutput = $this->filesystem->files[base_path('.env.encrypted')];

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
        $this->artisan('env:encrypt', ['--prune' => true])
            ->expectsQuestion('What encryption key would you like to use?', 'generate')
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->assertSame([base_path('.env.encrypted')], $this->filesystem->writes);
        $this->assertSame([base_path('.env')], $this->filesystem->deletes);
        $this->assertArrayNotHasKey(base_path('.env'), $this->filesystem->files);
    }

    public function testItEncryptsWithInteractivelyGivenKeyAndDisplaysIt(): void
    {
        $this->artisan('env:encrypt')
            ->expectsQuestion('What encryption key would you like to use?', 'ask')
            ->expectsQuestion('What is the encryption key?', $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP')
            ->expectsOutputToContain('Environment successfully encrypted')
            ->expectsOutputToContain($key)
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $encrypter = new Encrypter($key, 'AES-256-CBC');
        $this->assertSame('APP_NAME=Laravel', $encrypter->decrypt($this->filesystem->files[base_path('.env.encrypted')]));
    }
}
