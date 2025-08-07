<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\Eloquent\Model;

class DatabaseServiceProviderEloquentConfigTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset to default state
        Model::preventAccessingMissingAttributes(false);
    }

    public function testServiceProviderConfiguresEloquentFromConfig()
    {
        // Override the config for this test
        $this->app['config']->set('database.eloquent.prevent_accessing_missing_attributes', true);

        // Create and boot the service provider
        $provider = new DatabaseServiceProvider($this->app);
        $provider->boot();

        // Assert that the configuration was applied
        $this->assertTrue(Model::preventsAccessingMissingAttributes());
    }

    public function testServiceProviderDefaultsToDisabledWhenNotConfigured()
    {
        // Ensure config doesn't have the setting
        $this->app['config']->set('database.eloquent', []);

        // Create and boot the service provider
        $provider = new DatabaseServiceProvider($this->app);
        $provider->boot();

        // Assert that the feature is disabled by default
        $this->assertFalse(Model::preventsAccessingMissingAttributes());
    }

    public function testServiceProviderRespectsExplicitFalseConfig()
    {
        // Explicitly set to false
        $this->app['config']->set('database.eloquent.prevent_accessing_missing_attributes', false);

        // Create and boot the service provider
        $provider = new DatabaseServiceProvider($this->app);
        $provider->boot();

        // Assert that the feature is disabled
        $this->assertFalse(Model::preventsAccessingMissingAttributes());
    }
}
