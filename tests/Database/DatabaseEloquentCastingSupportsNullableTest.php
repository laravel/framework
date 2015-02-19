<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Model;

class DatabaseEloquentCastingSupportsNullableTest extends PHPUnit_Framework_TestCase {

    public function tearDown()
    {
        m::close();
    }

    public function testCastingStringToInteger()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('integer', $model->getAttribute('string_to_integer_field'));
    }


    public function testCastingStringToFloat()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('float', $model->getAttribute('string_to_float_field'));
    }


    public function testCastingStringToBoolean()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('boolean', $model->getAttribute('string_to_boolean_field'));
    }


    public function testCastingStringToArray()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('array', $model->getAttribute('json_string_to_array_field'));
    }


    public function testCastIntegerToString()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('string', $model->getAttribute('integer_to_string_field'));
    }


    public function testCastIntegerToBoolean()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('boolean', $model->getAttribute('integer_to_boolean_field'));
    }


    public function testCastFloatToString()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('string', $model->getAttribute('float_to_string_field'));
    }


    public function testCastNullableToString()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('null', $model->getAttribute('nullable_to_string'));
    }


    public function testCastNullableToInteger()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('null', $model->getAttribute('nullable_to_integer'));
    }


    public function testCastNullableToFloat()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('null', $model->getAttribute('nullable_to_float'));
    }


    public function testCastNullableToBoolean()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('null', $model->getAttribute('nullable_to_boolean'));
    }


    public function testCastNullableToArray()
    {
        $model = new EloquentModelCastingNullableStub();

        $this->assertInternalType('null', $model->getAttribute('nullable_to_array'));
    }

}


class EloquentModelCastingNullableStub extends Model {

    protected $table = 'stub';

    protected $guarded = array();

    protected $attributes = array(
        'string_to_integer_field' => '1',
        'string_to_float_field' => '1.0',
        'string_to_boolean_field' => '1',
        'json_string_to_array_field' => '{"foo": "bar"}',
        'integer_to_string_field' => 1,
        'integer_to_boolean_field' => 1,
        'float_to_string_field' => 1.2,
        'nullable_to_string' => null,
        'nullable_to_integer' => null,
        'nullable_to_float' => null,
        'nullable_to_boolean' => null,
        'nullable_to_array' => null,
    );

    protected $casts = array(
        'string_to_integer_field' => 'integer',
        'string_to_float_field' => 'float',
        'string_to_boolean_field' => 'boolean',
        'json_string_to_array_field' => 'array',
        'integer_to_string_field' => 'string',
        'integer_to_boolean_field' => 'boolean',
        'float_to_string_field' => 'string',
        'nullable_to_string' => 'string',
        'nullable_to_integer' => 'integer',
        'nullable_to_float' => 'float',
        'nullable_to_boolean' => 'boolean',
        'nullable_to_array' => 'array',
    );

}
