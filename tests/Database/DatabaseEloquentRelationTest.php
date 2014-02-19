<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DatabaseEloquentRelationTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testTouchMethodUpdatesRelatedTimestamps()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
		$builder->shouldReceive('getModel')->andReturn($related = m::mock('StdClass'));
		$builder->shouldReceive('where');
		$relation = new HasOne($builder, $parent, 'foreign_key', 'id');
		$related->shouldReceive('getTable')->andReturn('table');
		$related->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
		$related->shouldReceive('freshTimestampString')->andReturn(Carbon\Carbon::now());
		$builder->shouldReceive('update')->once()->with(array('updated_at' => Carbon\Carbon::now()));

		$relation->touch();
	}

}

class EloquentRelationResetModelStub extends Illuminate\Database\Eloquent\Model {}


class EloquentRelationResetStub extends Illuminate\Database\Eloquent\Builder {
	public function __construct() { $this->query = new EloquentRelationQueryStub; }
	public function getModel() { return new EloquentRelationResetModelStub; }
}


class EloquentRelationQueryStub extends Illuminate\Database\Query\Builder {
	public function __construct() {}
}