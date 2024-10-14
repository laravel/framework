<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelJsonCastingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use stdClass;

class EloquentModelJsonCastingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('json_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->json('basic_string_as_json_field')->nullable();
            $table->json('json_string_as_json_field')->nullable();
            $table->json('array_as_json_field')->nullable();
            $table->json('object_as_json_field')->nullable();
            $table->json('collection_as_json_field')->nullable();
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
            'collection_as_json_field' => new Collection(['key1' => 'value1']),
        ]);

        $this->assertInstanceOf(Collection::class, $user->collection_as_json_field);
        $this->assertSame('value1', $user->collection_as_json_field->get('key1'));
    }
}

/**
 * @property $basic_string_as_json_field
 * @property $json_string_as_json_field
 * @property $array_as_json_field
 * @property $object_as_json_field
 * @property $collection_as_json_field
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
    ];
}
