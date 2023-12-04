<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
            ->andReturn(true)->byDefault()
            ->shouldReceive('put')
            ->andReturn('APP_NAME=Laravel')->byDefault();
        File::swap($this->filesystem);
    }

    public function testItFailsWithInvalidCipherFails()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt', ['--cipher' => 'invalid'])
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

        $this->artisan('env:encrypt', ['--cipher' => 'aes-128-cbc', '--key' => 'invalid'])
            ->expectsOutputToContain('incorrect key length')
            ->assertExitCode(1);
    }

    public function testItGeneratesTheCorrectFileWhenUsingEnvironment()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false);

        $this->artisan('env:encrypt', ['--env' => 'production'])
            ->expectsOutputToContain('.env.production.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.production.encrypted'), m::any());
    }

    public function testItGeneratesTheCorrectFileWhenNotUsingEnvironment()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get');

        $this->artisan('env:encrypt')
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.encrypted'), m::any());
    }

    public function testItFailsWhenEnvironmentFileCannotBeFound()
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);

        $this->artisan('env:encrypt')
            ->expectsOutputToContain('Environment file not found.')
            ->assertExitCode(1);
    }

    public function testItFailsWhenEncryptionFileExists()
    {
        $this->filesystem->shouldReceive('exists')->andReturn(true);

        $this->artisan('env:encrypt')
            ->expectsOutputToContain('Encrypted environment file already exists.')
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

        $this->artisan('env:encrypt', ['--force' => true])
            ->expectsOutputToContain('.env.encrypted')
            ->assertExitCode(0);

        $this->filesystem->shouldHaveReceived('put')
            ->with(base_path('.env.encrypted'), m::any());
    }

    public function testItEncryptsWithGivenKeyAndDisplaysIt()
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

    public function testItEncryptsWithGivenGeneratedBase64KeyAndDisplaysIt()
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

    public function testItEncryptsTheValuesOfAnEnvironment()
    {
        $contents = <<<'Text'
        APP_NAME=Laravel
        APP_ENV=local
        APP_DEBUG=true
        APP_URL=http://localhost
        Text;

        $this->filesystem->shouldReceive('exists')
            ->once()
            ->andReturn(true)
            ->shouldReceive('exists')
            ->once()
            ->andReturn(false)
            ->shouldReceive('get')
            ->andReturn($contents)
            ->shouldReceive('put')
            ->withArgs(function ($file, $contents) {
                $encrypter = new Encrypter('abcdefghijklmnopabcdefghijklmnop', 'AES-256-CBC');

                $this->assertStringContainsString('APP_NAME', $contents);
                $this->assertStringContainsString('APP_ENV', $contents);
                $this->assertStringContainsString('APP_DEBUG', $contents);
                $this->assertStringContainsString('APP_URL', $contents);

                $this->assertEquals('Laravel', $encrypter->decrypt(Str::betweenFirst($contents, '=', "\n")));

                return true;
            })
            ->andReturn(true);

        $this->artisan('env:encrypt', ['--env' => 'production', '--key' => 'abcdefghijklmnopabcdefghijklmnop', '--only-values' => true])
            ->expectsOutputToContain('Environment successfully encrypted.')
            ->assertExitCode(0);
    }
}
