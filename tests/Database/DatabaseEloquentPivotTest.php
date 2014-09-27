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
		$parent = m::mock('Illuminate\Database\Eloquent\Model[getConnectionName]');
		$parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
		$pivot = new Pivot($parent, ['foo' => 'bar'], 'table', true);

		$this->assertEquals(['foo' => 'bar'], $pivot->getAttributes());
		$this->assertEquals('connection', $pivot->getConnectionName());
		$this->assertEquals('table', $pivot->getTable());
		$this->assertTrue($pivot->exists);
	}


	public function testPropertiesUnchangedAreNotDirty()
	{
		$parent = m::mock('Illuminate\Database\Eloquent\Model[getConnectionName]');
		$parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
		$pivot = new Pivot($parent, ['foo' => 'bar', 'shimy' => 'shake'], 'table', true);

		$this->assertEquals([], $pivot->getDirty());
	}


	public function testPropertiesChangedAreDirty()
	{
		$parent = m::mock('Illuminate\Database\Eloquent\Model[getConnectionName]');
		$parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
		$pivot = new Pivot($parent, ['foo' => 'bar', 'shimy' => 'shake'], 'table', true);
		$pivot->shimy = 'changed';

		$this->assertEquals(['shimy' => 'changed'], $pivot->getDirty());
	}


	public function testTimestampPropertyIsSetIfCreatedAtInAttributes()
	{
		$parent = m::mock('Illuminate\Database\Eloquent\Model[getConnectionName,getDates]');
		$parent->shouldReceive('getConnectionName')->andReturn('connection');
		$parent->shouldReceive('getDates')->andReturn([]);
		$pivot = new DatabaseEloquentPivotTestDateStub($parent, ['foo' => 'bar', 'created_at' => 'foo'], 'table');
		$this->assertTrue($pivot->timestamps);

		$pivot = new DatabaseEloquentPivotTestDateStub($parent, ['foo' => 'bar'], 'table');
		$this->assertFalse($pivot->timestamps);
	}


	public function testKeysCanBeSetProperly()
	{
		$parent = m::mock('Illuminate\Database\Eloquent\Model[getConnectionName]');
		$parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
		$pivot = new Pivot($parent, ['foo' => 'bar'], 'table');
		$pivot->setPivotKeys('foreign', 'other');

		$this->assertEquals('foreign', $pivot->getForeignKey());
		$this->assertEquals('other', $pivot->getOtherKey());
	}


	public function testDeleteMethodDeletesModelByKeys()
	{
		$parent = m::mock('Illuminate\Database\Eloquent\Model[getConnectionName]');
		$parent->guard([]);
		$parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
		$pivot = $this->getMock('Illuminate\Database\Eloquent\Relations\Pivot', ['newQuery'], [$parent, ['foo' => 'bar'], 'table']);
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


class DatabaseEloquentPivotTestModelStub extends Illuminate\Database\Eloquent\Model {}

class DatabaseEloquentPivotTestDateStub extends Illuminate\Database\Eloquent\Relations\Pivot {
	public function getDates()
	{
		return [];
	}
}
