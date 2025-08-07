<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\Eloquent\Model;

class DatabaseServiceProviderEnvironmentConfigTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset to default state
        Model::preventAccessingMissingAttributes(false);

        // Clean up environment variable
        if (isset($_ENV['DB_PREVENT_MISSING_ATTRIBUTES'])) {
            unset($_ENV['DB_PREVENT_MISSING_ATTRIBUTES']);
        }
    }

    public function testEnvironmentVariableEnablesFeature()
    {
        // Simulate environment variable
        $_ENV['DB_PREVENT_MISSING_ATTRIBUTES'] = 'true';

        // Update config to read from environment
        $this->app['config']->set('database.eloquent.prevent_accessing_missing_attributes', env('DB_PREVENT_MISSING_ATTRIBUTES', false));

        // Create and boot the service provider
        $provider = new DatabaseServiceProvider($this->app);
        $provider->boot();

        // Assert that the configuration was applied
        $this->assertTrue(Model::preventsAccessingMissingAttributes());
    }

    public function testEnvironmentVariableCanDisableFeature()
    {
        // Simulate environment variable set to false
        $_ENV['DB_PREVENT_MISSING_ATTRIBUTES'] = 'false';

        // Update config to read from environment
        $this->app['config']->set('database.eloquent.prevent_accessing_missing_attributes', env('DB_PREVENT_MISSING_ATTRIBUTES', false));

        // Create and boot the service provider
        $provider = new DatabaseServiceProvider($this->app);
        $provider->boot();

        // Assert that the feature is disabled
        $this->assertFalse(Model::preventsAccessingMissingAttributes());
    }

    public function testDefaultsToFalseWhenEnvironmentVariableNotSet()
    {
        // Ensure environment variable is not set
        if (isset($_ENV['DB_PREVENT_MISSING_ATTRIBUTES'])) {
            unset($_ENV['DB_PREVENT_MISSING_ATTRIBUTES']);
        }

        // Update config to read from environment with default false
        $this->app['config']->set('database.eloquent.prevent_accessing_missing_attributes', env('DB_PREVENT_MISSING_ATTRIBUTES', false));

        // Create and boot the service provider
        $provider = new DatabaseServiceProvider($this->app);
        $provider->boot();

        // Assert that the feature is disabled by default
        $this->assertFalse(Model::preventsAccessingMissingAttributes());
    }
}
