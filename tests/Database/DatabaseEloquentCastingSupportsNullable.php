<?php

use Illuminate\Database\Eloquent\Model;

class DatabaseEloquentCastingSupportsNullable extends PHPUnit_Framework_TestCase {

    public function testCastingStringToInteger()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('string_to_integer_field', '1');

        $this->assert(true, is_integer($model->getAttribute('string_to_integer_field')));
    }


    public function testFalseCastingStringToInteger()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('string_to_integer_field', '1');

        $this->assert(false, is_string($model->getAttribute('string_to_integer_field')));
    }


    public function testCastingStringToFloat()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('string_to_float_field', '1.0');

        $this->assert(true, is_float($model->getAttribute('string_to_float_field')));
    }


    public function testFalseCastingStringToFloat()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('string_to_float_field', '1.0');

        $this->assert(false, is_string($model->getAttribute('string_to_float_field')));
    }


    public function testCastingStringToBoolean()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('string_to_boolean_field', '1');

        $this->assert(true, is_bool($model->getAttribute('string_to_boolean_field')));
    }


    public function testFalseCastingStringToBoolean()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('string_to_boolean_field', '1');

        $this->assert(false, is_string($model->getAttribute('string_to_boolean_field')));
    }


    public function testCastingStringToArray()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('json_string_to_array_field', json_encode(array('foo' => 'bar')));

        $this->assert(true, is_array($model->getAttribute('json_string_to_array_field')));
    }


    public function testFalseCastingStringToArray()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('json_string_to_array_field', json_encode(array('foo' => 'bar')));

        $this->assert(false, is_string($model->getAttribute('json_string_to_array_field')));
    }


    public function testCastIntegerToString()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('integer_to_string_field', 1);

        $this->assert(true, is_string($model->getAttribute('integer_to_string_field')));
    }


    public function testFalseCastIntegerToString()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('integer_to_string_field', 1);

        $this->assert(false, is_integer($model->getAttribute('integer_to_string_field')));
    }


    public function testCastIntegerToBoolean()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('integer_to_boolean_field', 1);

        $this->assert(true, is_bool($model->getAttribute('integer_to_boolean_field')));
    }


    public function testFalseCastIntegerToBoolean()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('integer_to_boolean_field', 1);

        $this->assert(false, is_integer($model->getAttribute('integer_to_boolean_field')));
    }


    public function testCastFloatToString()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('integer_to_string_field', 1.0);

        $this->assert(true, is_string($model->getAttribute('float_to_string_field')));
    }


    public function testFalseCastFloatToString()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('integer_to_string_field', 1.0);

        $this->assert(false, is_integer($model->getAttribute('float_to_string_field')));
    }


    public function testCastNullableToString()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('nullable_to_string', null);

        $this->assert(true, is_null($model->getAttribute('nullable_to_string')));
    }


    public function testCastNullableToInteger()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('nullable_to_integer', null);

        $this->assert(true, is_null($model->getAttribute('nullable_to_integer')));
    }


    public function testCastNullableToFloat()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('nullable_to_float', null);

        $this->assert(true, is_null($model->getAttribute('nullable_to_float')));
    }


    public function testCastNullableToBoolean()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('nullable_to_boolean', null);

        $this->assert(true, is_null($model->getAttribute('nullable_to_boolean')));
    }


    public function testCastNullableToArray()
    {
        $model = new EloquentModelCastingStub();
        $model->setAttribute('nullable_to_array', null);

        $this->assert(true, is_null($model->getAttribute('nullable_to_array')));
    }

}


class EloquentModelCastingStub extends Model {

    protected $table = 'stub';

    protected $guarded = array();

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
