<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;

/**
 * @group database
 * @group eloquent
 * @group serialization
 * @group property-hooks
 */
class ModelPropertyHookSerializationTest extends TestCase
{
    /**
     * Test serialization behavior based on Model.php __sleep() implementation
     */
    public function test_serialization_bug_based_on_model_code()
    {
        $modelFile = __DIR__ . '/../../src/Illuminate/Database/Eloquent/Model.php';
        $modelContent = file_get_contents($modelFile);

        $user = new SerializableUser();

        if (str_contains($modelContent, 'get_mangled_object_vars')) {
            // Fixed version: serialization should work
            $serialized = serialize($user);
            $restored = unserialize($serialized);

            $this->assertInstanceOf(SerializableUser::class, $restored);
            $this->assertEquals('Ali Rezaei', $restored->full_name);
            $this->assertEquals('AR', $restored->initials);
        } else {
            // Old version: should fail
            $this->expectException(\Error::class);
            serialize($user);
        }
    }
}

/**
 * Named user classes for different test cases
 */
class SerializableUser extends Model
{
    protected $attributes = [
        'first_name' => 'Ali',
        'last_name'  => 'Rezaei',
    ];

    public string $full_name {
        get => "{$this->first_name} {$this->last_name}";
    }

    public string $initials {
        get => strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }
}

class BasicUser extends Model
{
    protected $attributes = [
        'first_name' => 'Kasra',
        'last_name'  => 'Mehrali',
    ];

    public string $full_name {
        get => "{$this->first_name} {$this->last_name}";
    }
}

class CalculatedUser extends Model
{
    protected $attributes = [
        'first_name' => 'john',
        'last_name'  => 'doe',
    ];

    public string $full_name {
        get => strtoupper("{$this->first_name} {$this->last_name}");
    }
}

class SimpleUser extends Model
{
    protected $attributes = [
        'name' => 'Ali',
    ];
}
