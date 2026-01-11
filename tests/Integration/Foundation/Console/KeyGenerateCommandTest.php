<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase;

class KeyGenerateCommandTest extends TestCase
{
    protected Filesystem $files;

    protected string $envPath;

    protected ?string $originalAppKey;

    protected function setUp(): void
    {
        $this->originalAppKey = $_ENV['APP_KEY'] ?? null;

        parent::setUp();

        $this->files = new Filesystem;
        $this->envPath = $this->app->environmentFilePath();
    }

    protected function tearDown(): void
    {
        if ($this->originalAppKey !== null) {
            $_ENV['APP_KEY'] = $this->originalAppKey;
        } else {
            unset($_ENV['APP_KEY']);
        }

        $this->files->delete($this->envPath);

        parent::tearDown();
    }

    public function testKeyGenerateShowsCorrectErrorWhenAppKeyIsSetInEnvironment()
    {
        $this->files->put($this->envPath, 'APP_KEY=');

        $envKeyValue = 'base64:'.base64_encode(random_bytes(32));
        $_ENV['APP_KEY'] = $envKeyValue;
        $this->app['config']->set('app.key', $envKeyValue);

        $this->artisan('key:generate')
            ->expectsOutputToContain('APP_KEY is set in the environment which prevents updating the .env file')
            ->assertExitCode(0);
    }

    public function testKeyGenerateShowsCorrectErrorWhenAppKeyIsMissingFromEnvFile()
    {
        $this->files->put($this->envPath, 'APP_NAME=Laravel');

        if ($this->originalAppKey !== null) {
            unset($_ENV['APP_KEY']);
        }

        $this->artisan('key:generate')
            ->expectsOutputToContain('No APP_KEY variable was found in the .env file')
            ->assertExitCode(0);
    }
}
