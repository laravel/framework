<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EnvironmentEncryptIncrementalCommandTest extends TestCase
{
    protected string $key = 'ANvVbPbE0tWMHpUySh6liY4WaCmAYKXP';

    protected function mockFiles(?string $source, ?string $previous, string $env = '.env'): void
    {
        File::swap(Mockery::mock(Filesystem::class));
        File::shouldReceive('exists')->with(base_path($env))->andReturn($source !== null);
        File::shouldReceive('exists')->with(base_path($env.'.encrypted'))->andReturn($previous !== null);
        File::shouldReceive('get')->with(base_path($env))->andReturn($source);
        File::shouldReceive('get')->with(base_path($env.'.encrypted'))->andReturn($previous);
    }

    protected function incrementalOptions(array $options = []): array
    {
        return array_merge(['--readable' => true, '--incremental' => true, '--key' => $this->key], $options);
    }

    #[DataProvider('ciphers')]
    public function testItChangesOnlyTheEditedValue(string $cipher): void
    {
        $encrypter = new Encrypter($this->key, $cipher);
        $host = $encrypter->encryptString('localhost');
        $password = $encrypter->encryptString('1');
        $app = $encrypter->encryptString('production');
        $source = <<<'ENV'
DB_HOST=localhost
DB_PASSWORD=2
APP_ENV=production

ENV;

        $previous = <<<ENV
DB_HOST=$host
DB_PASSWORD=$password
APP_ENV=$app

ENV;

        $this->mockFiles($source, $previous);

        $output = null;
        File::expects('put')->with(base_path('.env.encrypted'), Mockery::capture($output))->andReturn(100);

        $this->artisan('env:encrypt', $this->incrementalOptions(['--cipher' => $cipher]))->assertExitCode(0);

        $lines = explode("\n", $output);
        $this->assertSame("DB_HOST=$host", $lines[0]);
        $this->assertSame("APP_ENV=$app", $lines[2]);
        $this->assertSame('', $lines[3]);
        $payload = substr($lines[1], strlen('DB_PASSWORD='));
        $this->assertNotSame($password, $payload);
        $this->assertSame('2', $encrypter->decryptString($payload));
    }

    public static function ciphers(): array
    {
        return [['AES-256-CBC'], ['AES-256-GCM']];
    }

    public function testItDoesNotWriteWhenRawValuesAreUnchanged(): void
    {
        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $source = <<<'ENV'
DUP=1
DUP=2
MULTILINE="first
second"
REF="${DUP}"
EMPTY=

ENV;

        $multilineValue = <<<'VALUE'
"first
second"
VALUE;

        $first = $encrypter->encryptString('1');
        $second = $encrypter->encryptString('2');
        $multiline = $encrypter->encryptString($multilineValue);
        $reference = $encrypter->encryptString('"${DUP}"');
        $empty = $encrypter->encryptString('');

        $previous = <<<ENV
DUP=$first
DUP=$second
MULTILINE=$multiline
REF=$reference
EMPTY=$empty

ENV;

        $this->mockFiles($source, $previous);
        File::shouldReceive('put')->never();
        $this->artisan('env:encrypt', $this->incrementalOptions())->assertExitCode(0);
    }

    public function testItPreservesOccurrencesWhileAddingDeletingAndReordering(): void
    {
        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $first = $encrypter->encryptString('1');
        $second = $encrypter->encryptString('2');
        $removed = $encrypter->encryptString('secret');
        $source = <<<'ENV'
NEW=3
DUP=1
DUP=changed

ENV;

        $previous = <<<ENV
DUP=$first
REMOVED=$removed
DUP=$second

ENV;

        $this->mockFiles($source, $previous);
        $output = null;
        File::expects('put')->with(base_path('.env.encrypted'), Mockery::capture($output))->andReturn(100);

        $this->artisan('env:encrypt', $this->incrementalOptions())->assertExitCode(0);

        $lines = explode("\n", $output);
        $this->assertCount(4, $lines);
        $this->assertSame('3', $encrypter->decryptString(substr($lines[0], 4)));
        $this->assertSame("DUP=$first", $lines[1]);
        $this->assertSame('changed', $encrypter->decryptString(substr($lines[2], 4)));
        $this->assertStringNotContainsString('REMOVED=', $output);
    }

    public function testItCreatesAMissingTargetForTheSelectedEnvironment(): void
    {
        $this->mockFiles("DB_PASSWORD=1\n", null, '.env.production');
        $output = null;
        File::expects('put')->with(base_path('.env.production.encrypted'), Mockery::capture($output))->andReturn(100);

        $this->artisan('env:encrypt', $this->incrementalOptions(['--env' => 'production', '--key' => 'base64:'.base64_encode($this->key)]))->assertExitCode(0);

        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $this->assertSame('1', $encrypter->decryptString(substr(rtrim($output), strlen('DB_PASSWORD='))));
    }

    public function testItTreatsQuoteChangesAsRawValueChanges(): void
    {
        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $old = $encrypter->encryptString('1');
        $this->mockFiles('DB_PASSWORD="1"', 'DB_PASSWORD='.$old."\n");
        $output = null;
        File::expects('put')->with(base_path('.env.encrypted'), Mockery::capture($output))->andReturn(100);

        $this->artisan('env:encrypt', $this->incrementalOptions())->assertExitCode(0);

        $this->assertSame('"1"', $encrypter->decryptString(substr(rtrim($output), strlen('DB_PASSWORD='))));
    }

    public function testForceStillReencryptsAllValuesUnderANewKey(): void
    {
        $oldEncrypter = new Encrypter(str_repeat('x', 32), 'AES-256-CBC');
        $this->mockFiles('DB_PASSWORD=1', 'DB_PASSWORD='.$oldEncrypter->encryptString('1')."\n");
        $output = null;
        File::expects('put')->with(base_path('.env.encrypted'), Mockery::capture($output))->andReturn(100);

        $this->artisan('env:encrypt', ['--readable' => true, '--force' => true, '--key' => $this->key])->assertExitCode(0);

        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $this->assertSame('1', $encrypter->decryptString(substr(rtrim($output), strlen('DB_PASSWORD='))));
    }

    public function testItCanDeleteAllEntries(): void
    {
        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $this->mockFiles('', 'DB_PASSWORD='.$encrypter->encryptString('1')."\n");
        File::expects('put')->with(base_path('.env.encrypted'), '')->andReturn(0);
        $this->artisan('env:encrypt', $this->incrementalOptions())->assertExitCode(0);
    }

    #[DataProvider('invalidBaselines')]
    public function testItRejectsInvalidBaselinesWithoutWritingOrPruning(string $kind): void
    {
        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $previous = match ($kind) {
            'blob' => $encrypter->encrypt("DB_PASSWORD=1\n"),
            'wrong-key' => 'DB_PASSWORD='.(new Encrypter(str_repeat('x', 32), 'AES-256-CBC'))->encryptString('1'),
            'wrong-cipher' => 'DB_PASSWORD='.(new Encrypter($this->key, 'AES-256-GCM'))->encryptString('1'),
            'malformed' => '<<<<<<< HEAD',
            'corrupt-deleted-entry' => 'REMOVED=invalid',
        };
        $this->mockFiles("DB_PASSWORD=1\n", $previous);
        File::shouldReceive('put')->never();
        File::shouldReceive('delete')->never();
        $this->artisan('env:encrypt', $this->incrementalOptions(['--force' => true, '--prune' => true]))->assertExitCode(1);
    }

    public static function invalidBaselines(): array
    {
        return array_map(fn ($kind) => [$kind], ['blob', 'wrong-key', 'wrong-cipher', 'malformed', 'corrupt-deleted-entry']);
    }

    public function testItRequiresReadable(): void
    {
        $this->artisan('env:encrypt', ['--incremental' => true])
            ->expectsOutputToContain('The --incremental option requires --readable.')
            ->assertExitCode(1);
    }

    public function testItRejectsAMissingSource(): void
    {
        $this->mockFiles(null, 'existing');
        File::shouldReceive('put')->never();
        $this->artisan('env:encrypt', $this->incrementalOptions())
            ->expectsOutputToContain('Environment file not found.')
            ->assertExitCode(1);
    }

    public function testItRequiresTheExistingKeyInsteadOfGeneratingOne(): void
    {
        $this->mockFiles('DB_PASSWORD=1', 'existing');
        File::shouldReceive('put')->never();
        $this->artisan('env:encrypt', ['--readable' => true, '--incremental' => true, '--no-interaction' => true])
            ->expectsOutputToContain('The existing encryption key is required')
            ->assertExitCode(1);
    }

    public function testItAsksForTheExistingKeyWhenUpdatingInteractively(): void
    {
        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $this->mockFiles('DB_PASSWORD=1', 'DB_PASSWORD='.$encrypter->encryptString('1')."\n");
        File::shouldReceive('put')->never();
        $this->artisan('env:encrypt', ['--readable' => true, '--incremental' => true])
            ->expectsQuestion('What is the encryption key?', $this->key)
            ->assertExitCode(0);
    }

    public function testItDoesNotPruneAfterAFailedWrite(): void
    {
        $this->mockFiles('DB_PASSWORD=1', null);
        File::expects('put')->andReturn(false);
        File::shouldReceive('delete')->never();
        $this->artisan('env:encrypt', $this->incrementalOptions(['--prune' => true]))
            ->expectsOutputToContain('Unable to write the encrypted environment file.')
            ->assertExitCode(1);
    }

    #[DataProvider('baselineFormatting')]
    public function testItPreservesBaselineBytesWhenEntriesAreUnchanged(string $separator, string $prefix, string $suffix): void
    {
        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $first = $encrypter->encryptString('1');
        $second = $encrypter->encryptString('2');
        $previous = $prefix."FIRST=$first".$separator."SECOND=$second".$suffix;
        $source = <<<'ENV'
FIRST=1
SECOND=2

ENV;

        $this->mockFiles($source, $previous);
        File::shouldReceive('put')->never();
        $this->artisan('env:encrypt', $this->incrementalOptions())->assertExitCode(0);
    }

    public static function baselineFormatting(): array
    {
        return [
            'CRLF' => ["\r\n", '', "\r\n"],
            'CR' => ["\r", '', "\r"],
            'no final newline' => ["\n", '', ''],
            'comments and whitespace' => ["\r\n  # between\r\n \t\r\n", "  # before\r\n\t\r\n", "\r\n# after\r\n"],
        ];
    }

    public function testItUpdatesEntriesInABaselineContainingComments(): void
    {
        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $first = $encrypter->encryptString('1');
        $second = $encrypter->encryptString('2');
        $source = <<<'ENV'
FIRST=1
SECOND=3

ENV;

        $previous = <<<ENV
 # comment
FIRST=$first
\t
SECOND=$second

ENV;

        $this->mockFiles($source, str_replace("\n", "\r\n", $previous));
        $output = null;
        File::expects('put')->with(base_path('.env.encrypted'), Mockery::capture($output))->andReturn(100);
        $this->artisan('env:encrypt', $this->incrementalOptions())->assertExitCode(0);

        $lines = explode("\n", $output);
        $this->assertCount(3, $lines);
        $this->assertSame("FIRST=$first", $lines[0]);
        $this->assertSame('3', $encrypter->decryptString(substr($lines[1], strlen('SECOND='))));
    }

    public function testItRejectsCorruptionAfterIgnorableBaselineLines(): void
    {
        $previous = <<<ENV
  # comment
\t
REMOVED=invalid

ENV;

        $this->mockFiles('FIRST=1', str_replace("\n", "\r\n", $previous));
        File::shouldReceive('put')->never();
        File::shouldReceive('delete')->never();
        $this->artisan('env:encrypt', $this->incrementalOptions(['--prune' => true]))
            ->expectsOutputToContain('Unable to decrypt the encrypted environment entry on line 3.')
            ->assertExitCode(1);
    }

    public function testItWritesAReorderedBaselineEvenWhenEveryValueIsUnchanged(): void
    {
        $encrypter = new Encrypter($this->key, 'AES-256-CBC');
        $first = $encrypter->encryptString('1');
        $second = $encrypter->encryptString('2');
        $source = <<<'ENV'
SECOND=2
FIRST=1

ENV;

        $previous = <<<ENV
FIRST=$first
SECOND=$second

ENV;

        $this->mockFiles($source, str_replace("\n", "\r\n", $previous));
        $output = null;
        File::expects('put')->with(base_path('.env.encrypted'), Mockery::capture($output))->andReturn(100);
        $this->artisan('env:encrypt', $this->incrementalOptions())->assertExitCode(0);

        $expected = <<<ENV
SECOND=$second
FIRST=$first

ENV;

        $this->assertSame($expected, $output);
    }

    public function testItRejectsAnEmptyInteractiveKeyWithoutOfferingGeneration(): void
    {
        $this->mockFiles('FIRST=1', 'existing');
        File::shouldReceive('put')->never();
        $this->artisan('env:encrypt', ['--readable' => true, '--incremental' => true])
            ->expectsQuestion('What is the encryption key?', '')
            ->expectsOutputToContain('The existing encryption key is required for incremental encryption.')
            ->assertExitCode(1);
    }

    public function testItRejectsAnExplicitEmptyKeyNoninteractively(): void
    {
        $this->mockFiles('FIRST=1', 'existing');
        File::shouldReceive('put')->never();
        $this->artisan('env:encrypt', $this->incrementalOptions(['--key' => '', '--no-interaction' => true]))
            ->expectsOutputToContain('The existing encryption key is required for incremental encryption.')
            ->assertExitCode(1);
    }

    public function testItRejectsAMissingSourceBeforeAskingForAKey(): void
    {
        $this->mockFiles(null, 'existing');
        File::shouldReceive('put')->never();
        $this->artisan('env:encrypt', ['--readable' => true, '--incremental' => true])
            ->expectsOutputToContain('Environment file not found.')
            ->assertExitCode(1);
    }
}
