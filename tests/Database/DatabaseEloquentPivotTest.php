<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DatabaseEloquentPivotTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPropertiesAreSetCorrectly()
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->twice()->andReturn('connection');
        $parent->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $parent->setDateFormat('Y-m-d H:i:s');
        $pivot = new Pivot($parent, ['foo' => 'bar', 'created_at' => '2015-09-12'], 'table', true);

        $this->assertEquals(['foo' => 'bar', 'created_at' => '2015-09-12 00:00:00'], $pivot->getAttributes());
        $this->assertEquals('connection', $pivot->getConnectionName());
        $this->assertEquals('table', $pivot->getTable());
        $this->assertTrue($pivot->exists);
    }

    public function testMutatorsAreCalledFromConstructor()
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $pivot = new DatabaseEloquentPivotTestMutatorStub($parent, ['foo' => 'bar'], 'table', true);

        $this->assertTrue($pivot->getMutatorCalled());
    }

    public function testFromRawAttributesDoesNotDoubleMutate()
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestJsonCastStub::fromRawAttributes($parent, ['foo' => json_encode(['name' => 'Taylor'])], 'table', true);

        $this->assertEquals(['name' => 'Taylor'], $pivot->foo);
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
        $pivot = $this->getMockBuilder('Illuminate\Database\Eloquent\Relations\Pivot')->setMethods(['newQuery'])->setConstructorArgs([$parent, ['foo' => 'bar'], 'table'])->getMock();
        $pivot->setPivotKeys('foreign', 'other');
        $pivot->foreign = 'foreign.value';
        $pivot->other = 'other.value';
        $query = m::mock('stdClass');
        $query->shouldReceive('where')->once()->with(['foreign' => 'foreign.value', 'other' => 'other.value'])->andReturn($query);
        $query->shouldReceive('delete')->once()->andReturn(true);
        $pivot->expects($this->once())->method('newQuery')->will($this->returnValue($query));

        $this->assertTrue($pivot->delete());
    }
}

class DatabaseEloquentPivotTestDateStub extends \Illuminate\Database\Eloquent\Relations\Pivot
{
    public function getDates()
    {
        return [];
    }
}

class DatabaseEloquentPivotTestMutatorStub extends \Illuminate\Database\Eloquent\Relations\Pivot
{
    private $mutatorCalled = false;

    public function setFooAttribute($value)
    {
        $this->mutatorCalled = true;

        return $value;
    }

    public function getMutatorCalled()
    {
        return $this->mutatorCalled;
    }
}

class DatabaseEloquentPivotTestJsonCastStub extends \Illuminate\Database\Eloquent\Relations\Pivot
{
    protected $casts = [
        'foo' => 'json',
    ];
}
