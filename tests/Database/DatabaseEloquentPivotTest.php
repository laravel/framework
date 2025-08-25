<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseEloquentPivotTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testPropertiesAreSetCorrectly()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->twice()->andReturn('connection');
        $parent->setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));
        $resolver->shouldReceive('connection')->andReturn($connection = m::mock(Connection::class));
        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar = m::mock(Grammar::class));
        $connection->shouldReceive('getPostProcessor')->andReturn($processor = m::mock(Processor::class));
        $parent->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $parent->setDateFormat('Y-m-d H:i:s');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar', 'created_at' => '2015-09-12'], 'table', true);

        $this->assertEquals(['foo' => 'bar', 'created_at' => '2015-09-12 00:00:00'], $pivot->getAttributes());
        $this->assertSame('connection', $pivot->getConnectionName());
        $this->assertSame('table', $pivot->getTable());
        $this->assertTrue($pivot->exists);
        $this->assertSame($parent, $pivot->pivotParent);
    }

    public function testMutatorsAreCalledFromConstructor()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestMutatorStub::fromAttributes($parent, ['foo' => 'bar'], 'table', true);

        $this->assertTrue($pivot->getMutatorCalled());
    }

    public function testFromRawAttributesDoesNotDoubleMutate()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestJsonCastStub::fromRawAttributes($parent, ['foo' => json_encode(['name' => 'Taylor'])], 'table', true);

        $this->assertEquals(['name' => 'Taylor'], $pivot->foo);
    }

    public function testFromRawAttributesDoesNotMutate()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestMutatorStub::fromRawAttributes($parent, ['foo' => 'bar'], 'table', true);

        $this->assertFalse($pivot->getMutatorCalled());
    }

    public function testPropertiesUnchangedAreNotDirty()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar', 'shimy' => 'shake'], 'table', true);

        $this->assertEquals([], $pivot->getDirty());
    }

    public function testPropertiesChangedAreDirty()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar', 'shimy' => 'shake'], 'table', true);
        $pivot->shimy = 'changed';

        $this->assertEquals(['shimy' => 'changed'], $pivot->getDirty());
    }

    public function testTimestampPropertyIsSetIfCreatedAtInAttributes()
    {
        $parent = m::mock(Model::class.'[getConnectionName,getDates]');
        $parent->shouldReceive('getConnectionName')->andReturn('connection');
        $parent->shouldReceive('getDates')->andReturn([]);
        $pivot = DatabaseEloquentPivotTestDateStub::fromAttributes($parent, ['foo' => 'bar', 'created_at' => 'foo'], 'table');
        $this->assertTrue($pivot->timestamps);

        $pivot = DatabaseEloquentPivotTestDateStub::fromAttributes($parent, ['foo' => 'bar'], 'table');
        $this->assertFalse($pivot->timestamps);
    }

    public function testTimestampPropertyIsTrueWhenCreatingFromRawAttributes()
    {
        $parent = m::mock(Model::class.'[getConnectionName,getDates]');
        $parent->shouldReceive('getConnectionName')->andReturn('connection');
        $pivot = Pivot::fromRawAttributes($parent, ['foo' => 'bar', 'created_at' => 'foo'], 'table');
        $this->assertTrue($pivot->timestamps);
    }

    public function testKeysCanBeSetProperly()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar'], 'table');
        $pivot->setPivotKeys('foreign', 'other');

        $this->assertSame('foreign', $pivot->getForeignKey());
        $this->assertSame('other', $pivot->getOtherKey());
    }

    public function testDeleteMethodDeletesModelByKeys()
    {
        $pivot = $this->getMockBuilder(Pivot::class)->onlyMethods(['newQueryWithoutRelationships'])->getMock();
        $pivot->setPivotKeys('foreign', 'other');
        $pivot->foreign = 'foreign.value';
        $pivot->other = 'other.value';
        $query = m::mock(stdClass::class);
        $query->shouldReceive('where')->once()->with(['foreign' => 'foreign.value', 'other' => 'other.value'])->andReturn($query);
        $query->shouldReceive('delete')->once()->andReturn(true);
        $pivot->expects($this->once())->method('newQueryWithoutRelationships')->willReturn($query);

        $rowsAffected = $pivot->delete();
        $this->assertEquals(1, $rowsAffected);
    }

    public function testPivotModelTableNameIsSingular()
    {
        $pivot = new Pivot;

        $this->assertSame('pivot', $pivot->getTable());
    }

    public function testPivotModelWithParentReturnsParentsTimestampColumns()
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('parent_created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('parent_updated_at');

        $pivotWithParent = new Pivot;
        $pivotWithParent->pivotParent = $parent;

        $this->assertSame('parent_created_at', $pivotWithParent->getCreatedAtColumn());
        $this->assertSame('parent_updated_at', $pivotWithParent->getUpdatedAtColumn());
    }

    public function testPivotModelWithoutParentReturnsModelTimestampColumns()
    {
        $model = new DummyModel;

        $pivotWithoutParent = new Pivot;

        $this->assertEquals($model->getCreatedAtColumn(), $pivotWithoutParent->getCreatedAtColumn());
        $this->assertEquals($model->getUpdatedAtColumn(), $pivotWithoutParent->getUpdatedAtColumn());
    }

    public function testWithoutRelations()
    {
        $original = new Pivot;

        $original->pivotParent = 'foo';
        $original->setRelation('bar', 'baz');

        $this->assertSame('baz', $original->getRelation('bar'));

        $pivot = $original->withoutRelations();

        $this->assertInstanceOf(Pivot::class, $pivot);
        $this->assertNotSame($pivot, $original);
        $this->assertSame('foo', $original->pivotParent);
        $this->assertNull($pivot->pivotParent);
        $this->assertTrue($original->relationLoaded('bar'));
        $this->assertFalse($pivot->relationLoaded('bar'));

        $pivot = $original->unsetRelations();

        $this->assertSame($pivot, $original);
        $this->assertNull($pivot->pivotParent);
        $this->assertFalse($pivot->relationLoaded('bar'));
    }
}

class DatabaseEloquentPivotTestDateStub extends Pivot
{
    public function getDates()
    {
        return [];
    }
}

class DatabaseEloquentPivotTestMutatorStub extends Pivot
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

class DatabaseEloquentPivotTestJsonCastStub extends Pivot
{
    protected $casts = [
        'foo' => 'json',
    ];
}

class DummyModel extends Model
{
    //
}
