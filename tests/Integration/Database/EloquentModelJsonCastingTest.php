<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelJsonCastingTest;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use stdClass;

class EloquentModelJsonCastingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('json_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->json('basic_string_as_json_field')->nullable();
            $table->json('json_string_as_json_field')->nullable();
            $table->json('array_as_json_field')->nullable();
            $table->json('object_as_json_field')->nullable();
            $table->json('collection_as_json_field')->nullable();
            $table->json('customcast_as_json_field')->nullable();
        });
    }

    public function testStringsAreCastable()
    {
        /** @var \Illuminate\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $object */
        $object = JsonCast::create([
            'basic_string_as_json_field' => 'this is a string',
            'json_string_as_json_field' => '{"key1":"value1"}',
        ]);

        $this->assertSame('this is a string', $object->basic_string_as_json_field);
        $this->assertSame('{"key1":"value1"}', $object->json_string_as_json_field);
    }

    public function testArraysAreCastable()
    {
        /** @var \Illuminate\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $object */
        $object = JsonCast::create([
            'array_as_json_field' => ['key1' => 'value1'],
        ]);

        $this->assertEquals(['key1' => 'value1'], $object->array_as_json_field);
    }

    public function testObjectsAreCastable()
    {
        $object = new stdClass;
        $object->key1 = 'value1';

        /** @var \Illuminate\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $user */
        $user = JsonCast::create([
            'object_as_json_field' => $object,
        ]);

        $this->assertInstanceOf(stdClass::class, $user->object_as_json_field);
        $this->assertSame('value1', $user->object_as_json_field->key1);
    }

    public function testCollectionsAreCastable()
    {
        /** @var \Illuminate\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $user */
        $user = JsonCast::create([
            'collection_as_json_field' => new Collection(['key1' => 'value1', 'key2' => 'value2']),
        ]);

        $this->assertInstanceOf(Collection::class, $user->collection_as_json_field);
        $this->assertSame('value1', $user->collection_as_json_field->get('key1'));

        $user->collection_as_json_field = new Collection(['key2' => 'value2', 'key1' => 'value1']);
        $this->assertFalse($user->isDirty());
    }

    public function testCustomCastsAreCastable()
    {
        /** @var \Illuminate\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $model */
        $model = JsonCast::create([
            'customcast_as_json_field' => new Driver('Taylor', '123-456-7890'),
        ]);

        $this->assertInstanceOf(Driver::class, $model->customcast_as_json_field);
        $this->assertSame('Taylor', $model->customcast_as_json_field->name);
        $this->assertSame('123-456-7890', $model->customcast_as_json_field->phone);
    }

    public function testCustomCastsSerializesCastableAttributesAndOriginalIsEquivalent()
    {
        $invertedArr = [
            'phone' => '123-456-7890',
            'name' => 'Taylor',
        ];

        $originalArr = [
            'name' => 'Taylor',
            'phone' => '123-456-7890',
        ];

        \DB::table('json_casts')->insert([
            'customcast_as_json_field' => json_encode($invertedArr)
        ]);

        /** @var \Illuminate\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $model */
        $model = JsonCast::first();

        $this->assertInstanceOf(Driver::class, $model->customcast_as_json_field);
        $this->assertSame('Taylor', $model->customcast_as_json_field->name);
        $this->assertSame('123-456-7890', $model->customcast_as_json_field->phone);
        $this->assertSame($originalArr, $model->customcast_as_json_field->toArray());

        $model->customcast_as_json_field = json_encode($originalArr);

        $this->assertTrue($model->isClean('customcast_as_json_field'));
        $this->assertTrue($model->originalIsEquivalent('customcast_as_json_field'));
    }
}

/**
 * @property $basic_string_as_json_field
 * @property $json_string_as_json_field
 * @property $array_as_json_field
 * @property $object_as_json_field
 * @property $collection_as_json_field
 * @property Driver|null $customcast_as_json_field
 */
class JsonCast extends Model
{
    public $table = 'json_casts';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'basic_string_as_json_field' => 'json',
        'json_string_as_json_field' => 'json',
        'array_as_json_field' => 'array',
        'object_as_json_field' => 'object',
        'collection_as_json_field' => 'collection',
        'customcast_as_json_field' => DriverCast::class,
    ];
}

/**
 * Eloquent Casts...
 */
class DriverCast implements CastsAttributes, SerializesCastableAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return Driver
     */
    public function get($model, $key, $value, $attributes)
    {
        $value = json_decode($value, true);

        return Driver::fromArray(is_array($value) ? $value : []);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  Driver|string|null  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        if ($value instanceof Driver) {
            $valueAsArray = $value->toArray();
        } else {
            $value = is_null($value) ? [] : json_decode($value, true);
            $valueAsArray = Driver::fromArray(is_array($value) ? $value : []);
        }

        return json_encode($valueAsArray);
    }

    /**
     * Get the serialized representation of the value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function serialize($model, string $key, $value, array $attributes)
    {
        if ($value instanceof Driver) {
            return $value->toArray();
        }
        if (is_array($value)) {
            return $value;
        }

        return null;
    }
}

class Driver
{
    /**
     * @var string|null
     */
    public $name;

    /**
     * @var string|null
     */
    public $phone;

    public function __construct($name, $phone)
    {
        $this->name = $name;
        $this->phone = $phone;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
        ];
    }

    /**
     * @param  array|null  $driver
     * @return Driver
     */
    public static function fromArray(array $driver = null)
    {
        $driver = is_array($driver) ? $driver : [];

        return new self(
            $driver['name'] ?? null,
            $driver['phone'] ?? null
        );
    }
}
