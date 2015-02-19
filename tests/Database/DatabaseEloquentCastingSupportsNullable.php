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

}


class EloquentModelCastingStub extends Model {

	protected $table = 'stub';
	
	protected $guarded = array();
	
	protected $casts = array(
		'string_to_integer_field' => 'integer',
		'string_to_float_field' => 'float',
		'integer_to_string_field' => 'string',
		'float_to_string_field' => 'string',
		'nullable_to_string' => 'string',
		'nullable_to_integer' => 'integer',
		'nullable_to_float' => 'float'
	);

}
