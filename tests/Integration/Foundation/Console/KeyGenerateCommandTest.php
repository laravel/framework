<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Encryption\Encrypter;
use Orchestra\Testbench\TestCase;

class KeyGenerateCommandTest extends TestCase
{
    protected $envPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envPath = $this->app->environmentPath().'/.env';

        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }

        parent::tearDown();
    }

    protected function createEnvFile($content)
    {
        file_put_contents($this->envPath, $content);
    }

    protected function getEnvFileContent()
    {
        return file_get_contents($this->envPath);
    }

    public function testItGeneratesAndDisplaysKeyWithShowOption(): void
    {
        $this->artisan('key:generate', ['--show' => true])
            ->expectsOutputToContain('base64:')
            ->assertExitCode(0);
    }

    public function testItSetsKeyInEnvironmentFile(): void
    {
        $this->createEnvFile("APP_KEY=\nAPP_ENV=local");

        $this->app['config']['app.key'] = '';

        $this->artisan('key:generate')
            ->expectsOutputToContain('Application key set successfully.')
            ->assertExitCode(0);

        $content = $this->getEnvFileContent();
        $this->assertStringContainsString('APP_KEY=base64:', $content);
        $this->assertStringNotContainsString('APP_PREVIOUS_KEYS', $content);
    }

    public function testItMovesExistingKeyToPreviousKeys(): void
    {
        $existingKey = 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC'));
        $this->createEnvFile("APP_KEY={$existingKey}\nAPP_ENV=local");

        $this->app['config']['app.key'] = $existingKey;

        $this->artisan('key:generate', ['--force' => true])
            ->expectsOutputToContain('Application key set successfully.')
            ->assertExitCode(0);

        $content = $this->getEnvFileContent();
        $this->assertStringContainsString('APP_KEY=base64:', $content);
        $this->assertStringContainsString("APP_PREVIOUS_KEYS={$existingKey}", $content);
        $this->assertStringNotContainsString("APP_KEY={$existingKey}", $content);
    }

    public function testItPreservesExistingPreviousKeysWhenAddingNew(): void
    {
        $oldKey1 = 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC'));
        $oldKey2 = 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC'));
        $currentKey = 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC'));
        $this->createEnvFile("APP_KEY={$currentKey}\nAPP_PREVIOUS_KEYS={$oldKey1},{$oldKey2}\nAPP_ENV=local");

        $this->app['config']['app.key'] = $currentKey;

        $this->artisan('key:generate', ['--force' => true])
            ->expectsOutputToContain('Application key set successfully.')
            ->assertExitCode(0);

        $content = $this->getEnvFileContent();
        $this->assertStringContainsString('APP_KEY=base64:', $content);
        $this->assertStringContainsString("APP_PREVIOUS_KEYS={$oldKey1},{$oldKey2},{$currentKey}", $content);
        $this->assertStringNotContainsString("APP_KEY={$currentKey}", $content);
    }

    public function testItCreatesAppPreviousKeysIfNotExists(): void
    {
        $existingKey = 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC'));
        $this->createEnvFile("APP_KEY={$existingKey}\nAPP_ENV=local\nAPP_DEBUG=true");

        $this->app['config']['app.key'] = $existingKey;

        $this->artisan('key:generate', ['--force' => true])
            ->expectsOutputToContain('Application key set successfully.')
            ->assertExitCode(0);

        $content = $this->getEnvFileContent();
        $lines = explode("\n", $content);

        $appKeyLineIndex = null;
        $appPreviousKeysLineIndex = null;

        foreach ($lines as $index => $line) {
            if (str_starts_with($line, 'APP_KEY=') && ! str_contains($line, $existingKey)) {
                $appKeyLineIndex = $index;
            }
            if (str_starts_with($line, "APP_PREVIOUS_KEYS={$existingKey}")) {
                $appPreviousKeysLineIndex = $index;
            }
        }

        $this->assertNotNull($appKeyLineIndex, 'APP_KEY not found');
        $this->assertNotNull($appPreviousKeysLineIndex, 'APP_PREVIOUS_KEYS not found');
        $this->assertGreaterThan($appKeyLineIndex, $appPreviousKeysLineIndex, 'APP_PREVIOUS_KEYS should be after APP_KEY');
    }

    public function testItDoesNotAddEmptyKeyToPreviousKeys(): void
    {
        $this->createEnvFile("APP_KEY=\nAPP_ENV=local");

        $this->app['config']['app.key'] = '';

        $this->artisan('key:generate')
            ->expectsOutputToContain('Application key set successfully.')
            ->assertExitCode(0);

        $content = $this->getEnvFileContent();
        $this->assertStringContainsString('APP_KEY=base64:', $content);
        $this->assertStringNotContainsString('APP_PREVIOUS_KEYS', $content);
    }

    public function testItRequiresConfirmationInProduction(): void
    {
        $existingKey = 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC'));
        $this->createEnvFile("APP_KEY={$existingKey}\nAPP_ENV=production");

        $this->app['config']['app.key'] = $existingKey;
        $this->app['env'] = 'production';

        $this->artisan('key:generate')
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertExitCode(0);

        $content = $this->getEnvFileContent();
        $this->assertStringContainsString("APP_KEY={$existingKey}", $content);
        $this->assertStringNotContainsString('APP_PREVIOUS_KEYS', $content);
    }

    public function testItForcesInProductionWithForceOption(): void
    {
        $existingKey = 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC'));
        $this->createEnvFile("APP_KEY={$existingKey}\nAPP_ENV=production");

        $this->app['config']['app.key'] = $existingKey;
        $this->app['env'] = 'production';

        $this->artisan('key:generate', ['--force' => true])
            ->expectsOutputToContain('Application key set successfully.')
            ->assertExitCode(0);

        $content = $this->getEnvFileContent();
        $this->assertStringContainsString('APP_KEY=base64:', $content);
        $this->assertStringContainsString("APP_PREVIOUS_KEYS={$existingKey}", $content);
    }

    public function testItFailsWhenAppKeyNotFoundInEnvFile(): void
    {
        $this->createEnvFile("APP_ENV=local\nAPP_DEBUG=true");

        $this->app['config']['app.key'] = '';

        $this->artisan('key:generate')
            ->expectsOutputToContain('Unable to set application key. No APP_KEY variable was found in the .env file.')
            ->assertExitCode(0);

        $content = $this->getEnvFileContent();
        $this->assertStringNotContainsString('APP_KEY=', $content);
    }
}
