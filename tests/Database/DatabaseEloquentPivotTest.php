<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Tests\App\Models\Relationships\DatabaseEloquentPivotTestDateStub;
use Illuminate\Tests\App\Models\Relationships\DatabaseEloquentPivotTestJsonCastStub;
use Illuminate\Tests\App\Models\Relationships\DatabaseEloquentPivotTestMutatorStub;
use Illuminate\Tests\App\Models\Relationships\DummyModel;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentPivotTest extends TestCase
{
    public function testPropertiesAreSetCorrectly()
    {
        $parent = Mockery::mock(Model::class.'[getConnectionName]');
        $parent->expects('getConnectionName')->times(2)->andReturn('connection');
        $resolver = Mockery::mock(ConnectionResolverInterface::class);
        $parent->setConnectionResolver($resolver);
        $connection = Mockery::mock(Connection::class);
        $resolver->expects('connection')->times(2)->andReturn($connection);
        $grammar = Mockery::mock(Grammar::class);
        $connection->expects('getQueryGrammar')->times(2)->andReturn($grammar);
        $processor = Mockery::mock(Processor::class);
        $parent->getConnection()->getQueryGrammar()->expects('getDateFormat')->andReturn('Y-m-d H:i:s');
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
        $parent = Mockery::mock(Model::class.'[getConnectionName]');
        $parent->expects('getConnectionName')->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestMutatorStub::fromAttributes($parent, ['foo' => 'bar'], 'table', true);

        $this->assertTrue($pivot->getMutatorCalled());
    }

    public function testFromRawAttributesDoesNotDoubleMutate()
    {
        $parent = Mockery::mock(Model::class.'[getConnectionName]');
        $parent->expects('getConnectionName')->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestJsonCastStub::fromRawAttributes($parent, ['foo' => json_encode(['name' => 'Taylor'])], 'table', true);

        $this->assertEquals(['name' => 'Taylor'], $pivot->foo);
    }

    public function testFromRawAttributesDoesNotMutate()
    {
        $parent = Mockery::mock(Model::class.'[getConnectionName]');
        $parent->expects('getConnectionName')->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestMutatorStub::fromRawAttributes($parent, ['foo' => 'bar'], 'table', true);

        $this->assertFalse($pivot->getMutatorCalled());
    }

    public function testPropertiesUnchangedAreNotDirty()
    {
        $parent = Mockery::mock(Model::class.'[getConnectionName]');
        $parent->expects('getConnectionName')->andReturn('connection');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar', 'shimy' => 'shake'], 'table', true);

        $this->assertSame([], $pivot->getDirty());
    }

    public function testPropertiesChangedAreDirty()
    {
        $parent = Mockery::mock(Model::class.'[getConnectionName]');
        $parent->expects('getConnectionName')->andReturn('connection');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar', 'shimy' => 'shake'], 'table', true);
        $pivot->shimy = 'changed';

        $this->assertEquals(['shimy' => 'changed'], $pivot->getDirty());
    }

    public function testTimestampPropertyIsSetIfCreatedAtInAttributes()
    {
        $parent = Mockery::mock(Model::class.'[getConnectionName,getDates]');
        $parent->expects('getConnectionName')->times(2)->andReturn('connection');
        $pivot = DatabaseEloquentPivotTestDateStub::fromAttributes($parent, ['foo' => 'bar', 'created_at' => 'foo'], 'table');
        $this->assertTrue($pivot->timestamps);

        $pivot = DatabaseEloquentPivotTestDateStub::fromAttributes($parent, ['foo' => 'bar'], 'table');
        $this->assertFalse($pivot->timestamps);
    }

    public function testTimestampPropertyIsTrueWhenCreatingFromRawAttributes()
    {
        $parent = Mockery::mock(Model::class.'[getConnectionName,getDates]');
        $parent->expects('getConnectionName')->andReturn('connection');
        $pivot = Pivot::fromRawAttributes($parent, ['foo' => 'bar', 'created_at' => 'foo'], 'table');
        $this->assertTrue($pivot->timestamps);
    }

    public function testKeysCanBeSetProperly()
    {
        $parent = Mockery::mock(Model::class.'[getConnectionName]');
        $parent->expects('getConnectionName')->andReturn('connection');
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
        $query = Mockery::mock(Builder::class);
        $query->expects('where')->with(['foreign' => 'foreign.value', 'other' => 'other.value'])->andReturn($query);
        $query->expects('delete')->andReturn(true);
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
        $parent = Mockery::mock(Model::class);
        $parent->expects('getCreatedAtColumn')->andReturn('parent_created_at');
        $parent->expects('getUpdatedAtColumn')->andReturn('parent_updated_at');

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
