<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\RequiresPhp;

#[RequiresPhp('>=8.4')]
class EloquentModelPropertyHooksTest extends DatabaseTestCase
{
    public function testModelWithPropertyHooksCanBeSerialized()
    {
        $model = new TestModelWithPropertyHooks;
        $model->first_name = 'John';
        $model->last_name = 'Doe';

        // Access the property hook to ensure it works
        $this->assertEquals('John Doe', $model->full_name);

        $serialized = serialize($model);

        $this->assertIsString($serialized);

        // Verify unserialization works
        $unserialized = unserialize($serialized);

        $this->assertEquals('John', $unserialized->first_name);
        $this->assertEquals('Doe', $unserialized->last_name);
        // Property hook should still work after unserialization
        $this->assertEquals('John Doe', $unserialized->full_name);
    }

    public function testModelWithMultiplePropertyHooksCanBeSerialized()
    {
        $model = new TestModelWithMultiplePropertyHooks;
        $model->first_name = 'John';
        $model->last_name = 'Doe';
        $model->middle_name = 'Smith';

        // Verify property hooks work before serialization
        $this->assertEquals('Doe John Smith', $model->full_name);
        $this->assertEquals('John Doe', $model->short_name);

        // Test serialization
        $serialized = serialize($model);
        $this->assertIsString($serialized);

        // Test unserialization
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(Model::class, $unserialized);

        // Verify the property hooks still work after unserialization
        $this->assertEquals('Doe John Smith', $unserialized->full_name);
        $this->assertEquals('John Doe', $unserialized->short_name);
    }

    public function testModelWithSetterPropertyHookCanBeSerialized()
    {
        $model = new TestModelWithSetterPropertyHook;
        $model->email = '  JOHN@EXAMPLE.COM  ';

        // Verify setter hook worked
        $this->assertEquals('john@example.com', $model->email);

        // Test serialization
        $serialized = serialize($model);
        $unserialized = unserialize($serialized);

        // Verify data persists after unserialization
        $this->assertEquals('john@example.com', $unserialized->email);
    }

    public function testModelWithPropertyHooksCanBeQueuedForRedis()
    {
        // This simulates what happens when a model is queued
        $model = new TestModelWithPropertyHooks;
        $model->first_name = 'John';
        $model->last_name = 'Doe';
        $model->middle_name = 'Smith';

        // Simulate what queue does
        $payload = serialize([
            'model' => $model,
            'some_data' => 'test'
        ]);

        $this->assertIsString($payload);

        // Unserialize the payload
        $restored = unserialize($payload);

        $this->assertIsArray($restored);
        $this->assertInstanceOf(Model::class, $restored['model']);
        $this->assertEquals('John', $restored['model']->first_name);
        $this->assertEquals('John Doe', $restored['model']->full_name);
    }

    public function testModelWithMixedPropertiesAndHooks()
    {
        // Test a model with both regular properties and property hooks
        $model = new TestModelWithMixedPropertiesAndHooks;
        $model->first_name = 'john';
        $model->last_name = 'doe';
        $model->metadata = ['role' => 'admin'];

        $this->assertEquals('JOHN', $model->display_name);

        $serialized = serialize($model);
        $unserialized = unserialize($serialized);

        // Regular properties should be preserved
        $this->assertEquals('john', $unserialized->first_name);
        $this->assertEquals('doe', $unserialized->last_name);
        $this->assertEquals(['role' => 'admin'], $unserialized->metadata);

        // Property hook should still work
        $this->assertEquals('JOHN', $unserialized->display_name);
    }
}

/*
|--------------------------------------------------------------------------
| Define the runtime-only test model classes that use PHP 8.4 property
| hook syntax. Wrapped in a namespace declaration inside eval() so the
| classes are created in the expected namespace and avoid parse errors
| on older PHP versions.
|--------------------------------------------------------------------------
*/

if (PHP_VERSION_ID >= 80400) {
    // Only define if not already defined (prevents redeclare errors).
    if (!class_exists(__NAMESPACE__ . '\\TestModelWithPropertyHooks', false)) {
        eval(<<<'PHP'
namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model as EloquentModel;

class TestModelWithPropertyHooks extends EloquentModel
{
    protected $table = 'test_model2';
    public $timestamps = false;
    protected $fillable = ['first_name', 'last_name', 'middle_name'];

    // Property hook - virtual property (PHP 8.4)
    public string $full_name {
        get => "{$this->first_name} {$this->last_name}";
    }
}

class TestModelWithMultiplePropertyHooks extends EloquentModel
{
    protected $table = 'test_model2';
    public $timestamps = false;
    protected $fillable = ['first_name', 'last_name', 'middle_name'];

    // Multiple property hooks
    public string $full_name {
        get => trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }

    public string $short_name {
        get => "{$this->first_name} {$this->last_name}";
    }
}

class TestModelWithSetterPropertyHook extends EloquentModel
{
    protected $table = 'test_model2';
    public $timestamps = false;
    protected $fillable = ['email'];

    private string $_email = '';

    // Property hook with both get and set
    public string $email {
        get => $this->_email;
        set (string $value) {
            $this->_email = strtolower(trim($value));
        }
    }
}

class TestModelWithMixedPropertiesAndHooks extends EloquentModel
{
    protected $table = 'test_model2';
    public $timestamps = false;
    protected $fillable = ['first_name', 'last_name'];

    // Regular property (will be serialized)
    public $metadata = ['key' => 'value'];

    // Property hook (should NOT be serialized as it's virtual)
    public string $display_name {
        get => strtoupper($this->first_name ?? '');
    }
}
PHP
        );
    }
}
