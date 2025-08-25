<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentRelationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSetRelationFail()
    {
        $parent = new EloquentRelationResetModelStub;
        $relation = new EloquentRelationResetModelStub;
        $parent->setRelation('test', $relation);
        $parent->setRelation('foo', 'bar');
        $this->assertArrayNotHasKey('foo', $parent->toArray());
    }

    public function testUnsetExistingRelation()
    {
        $parent = new EloquentRelationResetModelStub;
        $relation = new EloquentRelationResetModelStub;
        $parent->setRelation('foo', $relation);
        $parent->unsetRelation('foo');
        $this->assertFalse($parent->relationLoaded('foo'));
    }

    public function testTouchMethodUpdatesRelatedTimestamps()
    {
        $builder = m::mock(Builder::class);
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $related = m::mock(EloquentNoTouchingModelStub::class)->makePartial();
        $builder->shouldReceive('getModel')->andReturn($related);
        $builder->shouldReceive('whereNotNull');
        $builder->shouldReceive('where');
        $builder->shouldReceive('withoutGlobalScopes')->andReturn($builder);
        $relation = new HasOne($builder, $parent, 'foreign_key', 'id');
        $related->shouldReceive('getTable')->andReturn('table');
        $related->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $now = Carbon::now();
        $related->shouldReceive('freshTimestampString')->andReturn($now);
        $builder->shouldReceive('update')->once()->with(['updated_at' => $now]);

        $relation->touch();
    }

    public function testCanDisableParentTouchingForAllModels()
    {
        /** @var \Illuminate\Tests\Database\EloquentNoTouchingModelStub $related */
        $related = m::mock(EloquentNoTouchingModelStub::class)->makePartial();
        $related->shouldReceive('getUpdatedAtColumn')->never();
        $related->shouldReceive('freshTimestampString')->never();

        $this->assertFalse($related::isIgnoringTouch());

        Model::withoutTouching(function () use ($related) {
            $this->assertTrue($related::isIgnoringTouch());

            $builder = m::mock(Builder::class);
            $parent = m::mock(Model::class);

            $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
            $builder->shouldReceive('getModel')->andReturn($related);
            $builder->shouldReceive('whereNotNull');
            $builder->shouldReceive('where');
            $builder->shouldReceive('withoutGlobalScopes')->andReturn($builder);
            $relation = new HasOne($builder, $parent, 'foreign_key', 'id');
            $builder->shouldReceive('update')->never();

            $relation->touch();
        });

        $this->assertFalse($related::isIgnoringTouch());
    }

    public function testCanDisableTouchingForSpecificModel()
    {
        $related = m::mock(EloquentNoTouchingModelStub::class)->makePartial();
        $related->shouldReceive('getUpdatedAtColumn')->never();
        $related->shouldReceive('freshTimestampString')->never();

        $anotherRelated = m::mock(EloquentNoTouchingAnotherModelStub::class)->makePartial();

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($anotherRelated::isIgnoringTouch());

        EloquentNoTouchingModelStub::withoutTouching(function () use ($related, $anotherRelated) {
            $this->assertTrue($related::isIgnoringTouch());
            $this->assertFalse($anotherRelated::isIgnoringTouch());

            $builder = m::mock(Builder::class);
            $parent = m::mock(Model::class);

            $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
            $builder->shouldReceive('getModel')->andReturn($related);
            $builder->shouldReceive('whereNotNull');
            $builder->shouldReceive('where');
            $builder->shouldReceive('withoutGlobalScopes')->andReturnSelf();
            $relation = new HasOne($builder, $parent, 'foreign_key', 'id');
            $builder->shouldReceive('update')->never();

            $relation->touch();

            $anotherBuilder = m::mock(Builder::class);
            $anotherParent = m::mock(Model::class);

            $anotherParent->shouldReceive('getAttribute')->with('id')->andReturn(2);
            $anotherBuilder->shouldReceive('getModel')->andReturn($anotherRelated);
            $anotherBuilder->shouldReceive('whereNotNull');
            $anotherBuilder->shouldReceive('where');
            $anotherBuilder->shouldReceive('withoutGlobalScopes')->andReturnSelf();
            $anotherRelation = new HasOne($anotherBuilder, $anotherParent, 'foreign_key', 'id');
            $now = Carbon::now();
            $anotherRelated->shouldReceive('freshTimestampString')->andReturn($now);
            $anotherBuilder->shouldReceive('update')->once()->with(['updated_at' => $now]);

            $anotherRelation->touch();
        });

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($anotherRelated::isIgnoringTouch());
    }

    public function testParentModelIsNotTouchedWhenChildModelIsIgnored()
    {
        $related = m::mock(EloquentNoTouchingModelStub::class)->makePartial();
        $related->shouldReceive('getUpdatedAtColumn')->never();
        $related->shouldReceive('freshTimestampString')->never();

        $relatedChild = m::mock(EloquentNoTouchingChildModelStub::class)->makePartial();
        $relatedChild->shouldReceive('getUpdatedAtColumn')->never();
        $relatedChild->shouldReceive('freshTimestampString')->never();

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($relatedChild::isIgnoringTouch());

        EloquentNoTouchingModelStub::withoutTouching(function () use ($related, $relatedChild) {
            $this->assertTrue($related::isIgnoringTouch());
            $this->assertTrue($relatedChild::isIgnoringTouch());

            $builder = m::mock(Builder::class);
            $parent = m::mock(Model::class);

            $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
            $builder->shouldReceive('getModel')->andReturn($related);
            $builder->shouldReceive('whereNotNull');
            $builder->shouldReceive('where');
            $builder->shouldReceive('withoutGlobalScopes')->andReturnSelf();
            $relation = new HasOne($builder, $parent, 'foreign_key', 'id');
            $builder->shouldReceive('update')->never();

            $relation->touch();

            $anotherBuilder = m::mock(Builder::class);
            $anotherParent = m::mock(Model::class);

            $anotherParent->shouldReceive('getAttribute')->with('id')->andReturn(2);
            $anotherBuilder->shouldReceive('getModel')->andReturn($relatedChild);
            $anotherBuilder->shouldReceive('whereNotNull');
            $anotherBuilder->shouldReceive('where');
            $anotherBuilder->shouldReceive('withoutGlobalScopes')->andReturnSelf();
            $anotherRelation = new HasOne($anotherBuilder, $anotherParent, 'foreign_key', 'id');
            $anotherBuilder->shouldReceive('update')->never();

            $anotherRelation->touch();
        });

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($relatedChild::isIgnoringTouch());
    }

    public function testIgnoredModelsStateIsResetWhenThereAreExceptions()
    {
        $related = m::mock(EloquentNoTouchingModelStub::class)->makePartial();
        $related->shouldReceive('getUpdatedAtColumn')->never();
        $related->shouldReceive('freshTimestampString')->never();

        $relatedChild = m::mock(EloquentNoTouchingChildModelStub::class)->makePartial();
        $relatedChild->shouldReceive('getUpdatedAtColumn')->never();
        $relatedChild->shouldReceive('freshTimestampString')->never();

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($relatedChild::isIgnoringTouch());

        try {
            EloquentNoTouchingModelStub::withoutTouching(function () use ($related, $relatedChild) {
                $this->assertTrue($related::isIgnoringTouch());
                $this->assertTrue($relatedChild::isIgnoringTouch());

                throw new Exception;
            });

            $this->fail('Exception was not thrown');
        } catch (Exception) {
            // Does nothing.
        }

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($relatedChild::isIgnoringTouch());
    }

    public function testSettingMorphMapWithNumericArrayUsesTheTableNames()
    {
        Relation::morphMap([EloquentRelationResetModelStub::class]);

        $this->assertEquals([
            'reset' => EloquentRelationResetModelStub::class,
        ], Relation::morphMap());

        Relation::morphMap([], false);
    }

    public function testSettingMorphMapWithNumericKeys()
    {
        Relation::morphMap([1 => 'App\User']);

        $this->assertEquals([
            1 => 'App\User',
        ], Relation::morphMap());

        Relation::morphMap([], false);
    }

    public function testGetMorphAlias()
    {
        Relation::morphMap(['user' => 'App\User']);

        $this->assertSame('user', Relation::getMorphAlias('App\User'));
        $this->assertSame('Does\Not\Exist', Relation::getMorphAlias('Does\Not\Exist'));
    }

    public function testWithoutRelations()
    {
        $original = new EloquentNoTouchingModelStub;

        $original->setRelation('foo', 'baz');

        $this->assertSame('baz', $original->getRelation('foo'));

        $model = $original->withoutRelations();

        $this->assertInstanceOf(EloquentNoTouchingModelStub::class, $model);
        $this->assertTrue($original->relationLoaded('foo'));
        $this->assertFalse($model->relationLoaded('foo'));

        $model = $original->unsetRelations();

        $this->assertInstanceOf(EloquentNoTouchingModelStub::class, $model);
        $this->assertFalse($original->relationLoaded('foo'));
        $this->assertFalse($model->relationLoaded('foo'));
    }

    public function testMacroable()
    {
        Relation::macro('foo', function () {
            return 'foo';
        });

        $model = new EloquentRelationResetModelStub;
        $relation = new EloquentRelationStub($model->newQuery(), $model);

        $result = $relation->foo();
        $this->assertSame('foo', $result);
    }

    public function testIsRelationIgnoresAttribute()
    {
        $model = new EloquentRelationAndAttributeModelStub;

        $this->assertTrue($model->isRelation('parent'));
        $this->assertFalse($model->isRelation('field'));
    }
}

class EloquentRelationResetModelStub extends Model
{
    protected $table = 'reset';

    // Override method call which would normally go through __call()

    public function getQuery()
    {
        return $this->newQuery()->getQuery();
    }
}

class EloquentRelationStub extends Relation
{
    public function addConstraints()
    {
        //
    }

    public function addEagerConstraints(array $models)
    {
        //
    }

    public function initRelation(array $models, $relation)
    {
        //
    }

    public function match(array $models, Collection $results, $relation)
    {
        //
    }

    public function getResults()
    {
        //
    }
}

class EloquentNoTouchingModelStub extends Model
{
    protected $table = 'table';
    protected $attributes = [
        'id' => 1,
    ];
}

class EloquentNoTouchingChildModelStub extends EloquentNoTouchingModelStub
{
    //
}

class EloquentNoTouchingAnotherModelStub extends Model
{
    protected $table = 'another_table';
    protected $attributes = [
        'id' => 2,
    ];
}

class EloquentRelationAndAttributeModelStub extends Model
{
    protected $table = 'one_more_table';

    public function field(): Attribute
    {
        return new Attribute(
            function ($value) {
                return $value;
            },
            function ($value) {
                return $value;
            },
        );
    }

    public function parent()
    {
        return $this->belongsTo(self::class);
    }
}
