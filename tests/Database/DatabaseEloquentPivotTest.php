<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DatabaseEloquentPivotTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPropertiesAreSetCorrectly()
	{
		$pivot = new Pivot(array('foo' => 'bar'), 'table', 'connection', true);

		$this->assertEquals(array('foo' => 'bar'), $pivot->getAttributes());
		$this->assertEquals('connection', $pivot->getConnectionName());
		$this->assertEquals('table', $pivot->getTable());
		$this->assertTrue($pivot->exists);
	}


	public function testTimestampPropertyIsSetIfCreatedAtInAttributes()
	{
		$pivot = new Pivot(array('foo' => 'bar', 'created_at' => 'foo'), 'table', 'connection');
		$this->assertTrue($pivot->timestamps);

		$pivot = new Pivot(array('foo' => 'bar'), 'table', 'connection');
		$this->assertFalse($pivot->timestamps);
	}


	public function testKeysCanBeSetProperly()
	{
		$pivot = new Pivot(array('foo' => 'bar'), 'table', 'connection');
		$pivot->setPivotKeys('foreign', 'other');

		$this->assertEquals('foreign', $pivot->getForeignKey());
		$this->assertEquals('other', $pivot->getOtherKey());
	}


	public function testDeleteMethodDeletesModelByKeys()
	{
		$pivot = $this->getMock('Illuminate\Database\Eloquent\Relations\Pivot', array('newQuery'), array(array('foo' => 'bar'), 'table', 'connection'));
		$pivot->setPivotKeys('foreign', 'other');
		$pivot->foreign = 'foreign.value';
		$pivot->other = 'other.value';
		$query = m::mock('stdClass');
		$query->shouldReceive('where')->once()->with('foreign', 'foreign.value')->andReturn($query);
		$query->shouldReceive('where')->once()->with('other', 'other.value')->andReturn($query);
		$query->shouldReceive('delete')->once()->andReturn(true);
		$pivot->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		
		$this->assertTrue($pivot->delete());
	}

}