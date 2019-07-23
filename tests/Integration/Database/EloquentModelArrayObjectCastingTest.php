<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelDateCastingTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentModelDateCastingTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('test_model1', function ($table) {
            $table->increments('id');
            $table->json('array_object_field')->nullable();
        });
    }

    public function test_cast_array_object()
    {
        $user = TestModel1::create([
            'array_object_field' => '{"a":"1", "b":2}',
        ]);

        $this->assertInstanceOf(\ArrayObject::class, $user->array_object_field);
        $this->assertSame('1', $user->array_object_field['a']);
        $this->assertSame(2, $user->array_object_field->b);
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;

    public $casts = [
        'array_object_field' => 'array_object',
    ];
}
