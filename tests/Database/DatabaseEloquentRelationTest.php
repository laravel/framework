<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DatabaseEloquentRelationTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testWhereClausesCanBeRemoved()
	{
		// For this test it doesn't matter what type of relationship we have, so we'll just use HasOne
		$builder = new EloquentRelationResetStub;
		$parent = m::mock('Illuminate\Database\Eloquent\Model');
		$parent->shouldReceive('getKey')->andReturn(1);
		$relation = new HasOne($builder, $parent, 'foreign_key');
		$relation->where('foo', '=', 'bar');
		list($wheres, $bindings) = $relation->getAndResetWheres();

		$this->assertEquals('bar', $bindings[0]);
		$this->assertEquals('Basic', $wheres[0]['type']);
		$this->assertEquals('foo', $wheres[0]['column']);
		$this->assertEquals('bar', $wheres[0]['value']);
	}

}


class EloquentRelationResetStub extends Illuminate\Database\Eloquent\Builder {
	public function __construct() { $this->query = new EloquentRelationQueryStub; }
	public function getModel() {}
}


class EloquentRelationQueryStub extends Illuminate\Database\Query\Builder {
	public function __construct() {}
}