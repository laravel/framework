<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentMorphTest extends TestCase
{
    protected function tearDown(): void
    {
        Relation::morphMap([], false);
    }

    public function testMorphOneSetsProperConstraints()
    {
        $relation = $this->getRelationWithRealQuery(MorphOne::class);

        $this->assertSame('select * from "eloquent_morph_reset_model_stubs" where "table"."morph_type" = ? and "table"."morph_id" = ? and "table"."morph_id" is not null', $relation->toSql());
        $this->assertSame([EloquentMorphResetModelStub::class, 1], $relation->getBindings());
    }

    public function testMorphOneEagerConstraintsAreProperlyAdded()
    {
        $parent = new EloquentMorphResetModelStub;
        $parent->setKeyType('string');
        $relation = $this->getRelationWithRealQuery(MorphOne::class, $parent);

        $model1 = new EloquentMorphResetModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphResetModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);

        $this->assertSame('select * from "eloquent_morph_reset_model_stubs" where "table"."morph_type" = ? and "table"."morph_id" = ? and "table"."morph_id" is not null and "table"."morph_id" in (?, ?) and "table"."morph_type" = ?', $relation->toSql());
        $this->assertSame([EloquentMorphResetModelStub::class, '1', 1, 2, EloquentMorphResetModelStub::class], $relation->getBindings());
    }

    /**
     * Note that the tests are the exact same for morph many because the classes share this code...
     * Will still test to be safe.
     */
    public function testMorphManySetsProperConstraints()
    {
        $relation = $this->getRelationWithRealQuery(MorphMany::class);

        $this->assertSame('select * from "eloquent_morph_reset_model_stubs" where "table"."morph_type" = ? and "table"."morph_id" = ? and "table"."morph_id" is not null', $relation->toSql());
        $this->assertSame([EloquentMorphResetModelStub::class, 1], $relation->getBindings());
    }

    public function testMorphManyEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelationWithRealQuery(MorphMany::class);

        $model1 = new EloquentMorphResetModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphResetModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);

        $this->assertSame('select * from "eloquent_morph_reset_model_stubs" where "table"."morph_type" = ? and "table"."morph_id" = ? and "table"."morph_id" is not null and "table"."morph_id" in (1, 2) and "table"."morph_type" = ?', $relation->toSql());
        $this->assertSame([EloquentMorphResetModelStub::class, 1, EloquentMorphResetModelStub::class], $relation->getBindings());
    }

    protected function getRelationWithRealQuery(string $relation, ?Model $parent = null)
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $builder = (new Builder(new QueryBuilder($connection, new Grammar($connection), new Processor)))->setModel(new EloquentMorphResetModelStub);

        $parent ??= new EloquentMorphResetModelStub;
        $parent->id = 1;

        return new $relation($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }
}

class EloquentMorphResetModelStub extends Model
{
    //
}
