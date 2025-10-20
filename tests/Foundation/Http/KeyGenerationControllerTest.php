<?php

namespace Illuminate\Tests\Foundation\Http;

use Illuminate\Foundation\Http\Controllers\KeyGenerationController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;

class KeyGenerationControllerTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('app.cipher', 'AES-256-CBC');
    }

    public function test_returns_forbidden_when_debug_mode_is_disabled()
    {
        $this->app['config']->set('app.debug', false);

        $controller = $this->app->make(KeyGenerationController::class);
        $response = $controller->generate(new Request());

        $this->assertEquals(JsonResponse::HTTP_FORBIDDEN, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Key generation is only available in debug mode.', $data['message']);
    }

    public function test_returns_error_when_env_file_is_not_writable()
    {
        $this->app['config']->set('app.debug', true);

        // Use a non-existent path
        $this->app->useEnvironmentPath(sys_get_temp_dir());
        $this->app->loadEnvironmentFrom('.env.nonexistent');

        $controller = $this->app->make(KeyGenerationController::class);
        $response = $controller->generate(new Request());

        $this->assertEquals(JsonResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Unable to set application key', $data['message']);
    }

    public function test_generates_key_successfully_in_debug_mode()
    {
        $this->app['config']->set('app.debug', true);
        $this->app['config']->set('app.key', '');

        // Create a temporary environment file
        $envPath = sys_get_temp_dir().'/'.uniqid('.env.testing.');
        file_put_contents($envPath, "APP_NAME=Laravel\nAPP_KEY=\nAPP_DEBUG=true\n");

        $this->app->useEnvironmentPath(dirname($envPath));
        $this->app->loadEnvironmentFrom(basename($envPath));

        try {
            $controller = $this->app->make(KeyGenerationController::class);
            $response = $controller->generate(new Request());

            $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());

            $data = $response->getData(true);
            $this->assertTrue($data['success']);
            $this->assertEquals('Application key set successfully.', $data['message']);

            // Verify the key was written to the file
            $contents = file_get_contents($envPath);
            $this->assertStringContainsString('APP_KEY=base64:', $contents);
        } finally {
            // Cleanup
            if (file_exists($envPath)) {
                unlink($envPath);
            }
        }
    }

    public function test_updates_config_after_generating_key()
    {
        $this->app['config']->set('app.debug', true);
        $this->app['config']->set('app.key', '');

        // Create a temporary environment file
        $envPath = sys_get_temp_dir().'/'.uniqid('.env.testing.');
        file_put_contents($envPath, "APP_NAME=Laravel\nAPP_KEY=\nAPP_DEBUG=true\n");

        $this->app->useEnvironmentPath(dirname($envPath));
        $this->app->loadEnvironmentFrom(basename($envPath));

        try {
            $originalKey = $this->app['config']['app.key'];

            $controller = $this->app->make(KeyGenerationController::class);
            $controller->generate(new Request());

            $newKey = $this->app['config']['app.key'];

            $this->assertNotEquals($originalKey, $newKey);
            $this->assertStringStartsWith('base64:', $newKey);
        } finally {
            // Cleanup
            if (file_exists($envPath)) {
                unlink($envPath);
            }
        }
    }

    public function test_replaces_existing_key()
    {
        $this->app['config']->set('app.debug', true);

        $existingKey = 'base64:'.base64_encode(random_bytes(32));
        $this->app['config']->set('app.key', $existingKey);

        // Create a temporary environment file with existing key
        $envPath = sys_get_temp_dir().'/'.uniqid('.env.testing.');
        file_put_contents($envPath, "APP_NAME=Laravel\nAPP_KEY={$existingKey}\nAPP_DEBUG=true\n");

        $this->app->useEnvironmentPath(dirname($envPath));
        $this->app->loadEnvironmentFrom(basename($envPath));

        try {
            $controller = $this->app->make(KeyGenerationController::class);
            $response = $controller->generate(new Request());

            $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());

            // Verify the key was replaced
            $contents = file_get_contents($envPath);
            $this->assertStringContainsString('APP_KEY=base64:', $contents);
            $this->assertStringNotContainsString($existingKey, $contents);
        } finally {
            // Cleanup
            if (file_exists($envPath)) {
                unlink($envPath);
            }
        }
    }

    public function test_uses_same_key_replacement_pattern_as_key_generate_command()
    {
        $this->app['config']->set('app.debug', true);

        $existingKey = 'base64:'.base64_encode(random_bytes(32));
        $this->app['config']->set('app.key', $existingKey);

        // Create env file with different formats
        $envPath = sys_get_temp_dir().'/'.uniqid('.env.testing.');
        file_put_contents($envPath, "APP_NAME=Laravel\nAPP_KEY={$existingKey}\nAPP_DEBUG=true\n");

        $this->app->useEnvironmentPath(dirname($envPath));
        $this->app->loadEnvironmentFrom(basename($envPath));

        try {
            $controller = $this->app->make(KeyGenerationController::class);
            $response = $controller->generate(new Request());

            $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());

            // Verify the pattern worked correctly
            $contents = file_get_contents($envPath);
            $lines = explode("\n", $contents);
            $appKeyLine = array_filter($lines, fn ($line) => str_starts_with($line, 'APP_KEY='));

            $this->assertCount(1, $appKeyLine, 'Should have exactly one APP_KEY line');
        } finally {
            // Cleanup
            if (file_exists($envPath)) {
                unlink($envPath);
            }
        }
    }
}
