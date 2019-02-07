<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelJsonCastingTest;

use stdClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentModelJsonCastingTest extends DatabaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function ($table) {
            $table->increments('id');
            $table->json('basic_string_as_json_field')->nullable();
            $table->json('json_string_as_json_field')->nullable();
            $table->json('array_as_json_field')->nullable();
            $table->json('object_as_json_field')->nullable();
            $table->json('collection_as_json_field')->nullable();
        });
    }

    public function test_strings_are_castable()
    {
        $user = TestModel1::create([
            'basic_string_as_json_field' => 'this is a string',
            'json_string_as_json_field' => '{"key1":"value1"}',
        ]);

        $this->assertEquals('this is a string', $user->toArray()['basic_string_as_json_field']);
        $this->assertEquals('{"key1":"value1"}', $user->toArray()['json_string_as_json_field']);
    }

    public function test_arrays_are_castable()
    {
        $user = TestModel1::create([
            'array_as_json_field' => ['key1' => 'value1'],
        ]);

        $this->assertEquals(['key1' => 'value1'], $user->toArray()['array_as_json_field']);
    }

    public function test_objects_are_castable()
    {
        $object = new stdClass();
        $object->key1 = 'value1';

        $user = TestModel1::create([
            'object_as_json_field' => $object,
        ]);

        $this->assertInstanceOf(stdClass::class, $user->toArray()['object_as_json_field']);
        $this->assertEquals('value1', $user->toArray()['object_as_json_field']->key1);
    }

    public function test_collections_are_castable()
    {
        $user = TestModel1::create([
            'collection_as_json_field' => new Collection(['key1' => 'value1']),
        ]);

        $this->assertInstanceOf(Collection::class, $user->toArray()['collection_as_json_field']);
        $this->assertEquals('value1', $user->toArray()['collection_as_json_field']->get('key1'));
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = ['id'];

    public $casts = [
        'basic_string_as_json_field' => 'json',
        'json_string_as_json_field' => 'json',
        'array_as_json_field' => 'array',
        'object_as_json_field' => 'object',
        'collection_as_json_field' => 'collection',
    ];
}
