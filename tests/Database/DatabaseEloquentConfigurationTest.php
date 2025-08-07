<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset to default state
        Model::preventAccessingMissingAttributes(false);
    }

    public function testPreventAccessingMissingAttributesCanBeEnabledAndDisabled()
    {
        // Start with default behavior (disabled)
        $this->assertFalse(Model::preventsAccessingMissingAttributes());

        $model = new EloquentTestModelStub(['id' => 1]);
        $model->exists = true;

        // Should return null when disabled
        $this->assertNull($model->non_existent_attribute);

        // Enable the feature
        Model::preventAccessingMissingAttributes(true);
        $this->assertTrue(Model::preventsAccessingMissingAttributes());

        // Should throw exception when enabled
        $this->expectException(MissingAttributeException::class);
        $model->non_existent_attribute;
    }

    public function testPreventAccessingMissingAttributesCanBeDisabled()
    {
        // Enable the feature first
        Model::preventAccessingMissingAttributes(true);
        $this->assertTrue(Model::preventsAccessingMissingAttributes());

        $model = new EloquentTestModelStub(['id' => 1]);
        $model->exists = true;

        // Verify it throws when enabled
        try {
            $model->non_existent_attribute;
            $this->fail('Expected MissingAttributeException was not thrown');
        } catch (MissingAttributeException $e) {
            // Expected behavior
        }

        // Disable the feature
        Model::preventAccessingMissingAttributes(false);
        $this->assertFalse(Model::preventsAccessingMissingAttributes());

        // Should now return null instead of throwing
        $this->assertNull($model->non_existent_attribute);
    }

    public function testPreventAccessingMissingAttributesDefaultsToFalse()
    {
        // Reset to ensure clean state
        Model::preventAccessingMissingAttributes(false);

        // Assert that the feature is disabled by default
        $this->assertFalse(Model::preventsAccessingMissingAttributes());

        // Test that accessing missing attributes returns null
        $model = new EloquentTestModelStub(['id' => 1]);
        $model->exists = true;

        $this->assertNull($model->non_existent_attribute);
    }
}

class EloquentTestModelStub extends Model
{
    protected $fillable = ['id'];
    public $timestamps = false;
}
