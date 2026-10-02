<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use PDO;
use PHPUnit\Framework\TestCase;

class EloquentHasOneOrManyDeprecationTest extends TestCase
{
    public function testHasManyMatchWithNullLocalKey(): void
    {
        $relation = $this->getHasManyRelation();

        $result1 = new HasOneOrManyDeprecationModelStub;
        $result1->foreign_key = 1;

        $result2 = new HasOneOrManyDeprecationModelStub;
        $result2->foreign_key = '';

        $model1 = new HasOneOrManyDeprecationModelStub;
        $model1->id = 1;
        $model2 = new HasOneOrManyDeprecationModelStub;
        $model2->id = null;

        $models = $relation->match([$model1, $model2], new Collection([$result1, $result2]), 'foo');

        $this->assertCount(1, $models[0]->foo);
        $this->assertNull($models[1]->foo);
    }

    public function testHasOneMatchWithNullLocalKey(): void
    {
        $relation = $this->getHasOneRelation();

        $result1 = new HasOneOrManyDeprecationModelStub;
        $result1->foreign_key = 1;

        $model1 = new HasOneOrManyDeprecationModelStub;
        $model1->id = 1;
        $model2 = new HasOneOrManyDeprecationModelStub;
        $model2->id = null;

        $models = $relation->match([$model1, $model2], new Collection([$result1]), 'foo');

        $this->assertInstanceOf(HasOneOrManyDeprecationModelStub::class, $models[0]->foo);
        $this->assertNull($models[1]->foo);
    }

    protected function getHasManyRelation(): HasMany
    {
        return new HasMany($this->newBuilder(), $this->newParent(), 'table.foreign_key', 'id');
    }

    protected function getHasOneRelation(): HasOne
    {
        return new HasOne($this->newBuilder(), $this->newParent(), 'table.foreign_key', 'id');
    }

    protected function newBuilder(): Builder
    {
        $connection = new Connection(new PDO('sqlite::memory:'));

        return (new Builder(new QueryBuilder($connection, new Grammar($connection), new Processor)))->setModel(new HasOneOrManyDeprecationModelStub);
    }

    protected function newParent(): Model
    {
        $parent = new HasOneOrManyDeprecationModelStub;
        $parent->id = 1;

        return $parent;
    }
}

class HasOneOrManyDeprecationModelStub extends Model
{
    public $foreign_key;
}
