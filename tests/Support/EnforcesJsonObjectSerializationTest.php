<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Traits\EnforcesJsonObjectSerialization;

class EnforcesJsonObjectSerializationTest extends TestCase
{
    public function testEmptyArrayIsSerializedAsObject()
    {
        $collection = new TestCollection([]);
        $collection->serializeEmptyAsObject();

        $json = json_encode($collection);

        $this->assertSame('{}', $json);
    }

    public function testNonEmptyArrayIsSerializedAsArray()
    {
        $collection = new TestCollection(['item1', 'item2']);
        $collection->serializeEmptyAsObject();

        $json = json_encode($collection);

        $this->assertSame('["item1","item2"]', $json);
    }

    public function testEmptyAttributeIsSerializedAsObject()
    {
        $model = new TestModel([
            'name' => 'Test',
            'meta' => [],
            'settings' => [],
        ]);
        $model->serializeAttributesAsObjects(['meta']);

        $json = json_encode($model);
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertSame('Test', $decoded['name']);
        $this->assertIsObject(json_decode($json)->meta);
        $this->assertIsArray(json_decode($json)->settings);
    }

    public function testMultipleAttributesAreSerializedAsObjects()
    {
        $model = new TestModel([
            'name' => 'Test',
            'meta' => [],
            'settings' => [],
            'options' => [],
        ]);
        $model->serializeAttributesAsObjects(['meta', 'options']);

        $json = json_encode($model);
        $decoded = json_decode($json);

        $this->assertIsObject($decoded->meta);
        $this->assertIsObject($decoded->options);
        $this->assertIsArray($decoded->settings);
    }

    public function testNullAttributeRemainsNull()
    {
        $model = new TestModel([
            'name' => 'Test',
            'meta' => null,
        ]);
        $model->serializeAttributesAsObjects(['meta']);

        $json = json_encode($model);
        $decoded = json_decode($json);

        $this->assertNull($decoded->meta);
    }

    public function testAcceptsStringArgumentForSingleAttribute()
    {
        $model = new TestModel([
            'name' => 'Test',
            'meta' => [],
        ]);
        $model->serializeAttributesAsObjects('meta');

        $json = json_encode($model);
        $decoded = json_decode($json);

        $this->assertIsObject($decoded->meta);
    }

    public function testDefaultBehaviorDoesNotModifySerialization()
    {
        $collection = new TestCollection([]);
        $json = json_encode($collection);

        $this->assertSame('[]', $json);

        $model = new TestModel([
            'meta' => [],
        ]);
        $json = json_encode($model);
        $decoded = json_decode($json);

        $this->assertIsArray($decoded->meta);
    }
}

// Test helper classes

class TestCollection implements \JsonSerializable
{
    use EnforcesJsonObjectSerialization;

    protected array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function jsonSerialize(): mixed
    {
        return $this->enforceJsonObjectSerialization($this->items);
    }
}

class TestModel implements \JsonSerializable
{
    use EnforcesJsonObjectSerialization;

    protected array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function jsonSerialize(): mixed
    {
        return $this->enforceJsonObjectSerialization($this->attributes);
    }
}
