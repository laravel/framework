<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

class KeyGenerateCommandTest extends TestCase
{
    protected string $envFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envFilePath = $this->app->environmentFilePath();
    }

    protected function tearDown(): void
    {
        // Clean up any environment variables we set
        putenv('APP_KEY');
        $_ENV['APP_KEY'] = '';
        $_SERVER['APP_KEY'] = '';

        parent::tearDown();
    }

    public function test_key_generate_updates_env_file()
    {
        // Create .env file with empty APP_KEY
        file_put_contents($this->envFilePath, 'APP_KEY=');

        $this->artisan('key:generate')
            ->expectsOutputToContain('Application key set successfully')
            ->assertExitCode(0);

        $envContent = file_get_contents($this->envFilePath);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:.+$/m', $envContent);
    }

    /**
     * Test that key:generate writes to .env even when APP_KEY is set in environment.
     *
     * This can happen when:
     * - A parent process (like Laravel Horizon) has APP_KEY set in its environment
     * - The user has APP_KEY exported in their shell profile
     * - Running in a container with APP_KEY set as an environment variable
     *
     * The key:generate command should always write to .env when explicitly called,
     * regardless of whether APP_KEY exists in the environment.
     */
    public function test_key_generate_updates_env_file_even_when_app_key_exists_in_environment()
    {
        // Create .env file with empty APP_KEY
        file_put_contents($this->envFilePath, 'APP_KEY=');

        // Set APP_KEY in environment (simulating inheritance from parent process like Horizon)
        $existingKey = 'base64:'.base64_encode(Encrypter::generateKey($this->app['config']['app.cipher']));
        putenv("APP_KEY={$existingKey}");
        $_ENV['APP_KEY'] = $existingKey;
        $_SERVER['APP_KEY'] = $existingKey;

        // Force config to reload with the environment variable
        $this->app['config']->set('app.key', $existingKey);

        // Run key:generate --force
        // Expected: should update .env file with a new key
        // Current bug: command builds regex using env var value, which doesn't match .env content
        $this->artisan('key:generate', ['--force' => true])
            ->expectsOutputToContain('Application key set successfully')
            ->assertExitCode(0);

        $envContent = file_get_contents($this->envFilePath);

        // The .env file should have a new key written to it
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:.+$/m', $envContent);
    }

    public function test_key_generate_shows_helpful_error_when_app_key_line_missing_from_env()
    {
        // Create .env file WITHOUT APP_KEY line at all
        file_put_contents($this->envFilePath, 'APP_NAME=Laravel');

        $this->artisan('key:generate')
            ->expectsOutputToContain('No APP_KEY variable was found')
            ->assertExitCode(0);
    }
}
