<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Tests\Database\Fixtures\EloquentBuilderStub;
use JMac\Testing\Double;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyWithCastedAttributesTest extends TestCase
{
    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();
        $model1 = Double::for(Model::class)->passthru();
        $model1->allows('getAttribute')->with('parent_key')->returns(1);
        $model1->allows('hasGetMutator')->returns(false);
        $model1->allows('hasAttributeMutator')->returns(false);
        $model1->allows('hasRelationAutoloadCallback')->returns(false);
        $model1->allows('getCasts')->returns([]);

        $model2 = Double::for(Model::class)->passthru();
        $model2->allows('getAttribute')->with('parent_key')->returns(2);
        $model2->allows('hasGetMutator')->returns(false);
        $model2->allows('hasAttributeMutator')->returns(false);
        $model2->allows('hasRelationAutoloadCallback')->returns(false);
        $model2->allows('getCasts')->returns([]);

        $result1 = (object) [
            'pivot' => (object) [
                'foreign_key' => new class
                {
                    public function __toString()
                    {
                        return '1';
                    }
                },
            ],
        ];

        $models = $relation->match([$model1, $model2], Collection::wrap($result1), 'foo');
        $this->assertNull($models[1]->foo);
        $this->assertSame(1, $models[0]->foo->count());
        $this->assertContains($result1, $models[0]->foo);
    }

    protected function getRelation()
    {
        $builder = Double::for(EloquentBuilderStub::class);
        $related = Double::for(Model::class)->passthru();
        $builder->allows('getModel')->returns($related);
        $related->allows('qualifyColumn');
        $builder->allows('join');
        $builder->allows('where');
        $queryBuilder = Double::for(QueryBuilder::class);
        $queryBuilder->allows('getGrammar')->returns(new Grammar(Double::for(Connection::class)));

        $builder->allows('getQuery')->returns($queryBuilder);

        return new BelongsToMany(
            $builder,
            new EloquentBelongsToManyModelStub,
            'relation',
            'foreign_key',
            'id',
            'parent_key',
            'related_key'
        );
    }
}

class EloquentBelongsToManyModelStub extends Model
{
    public $foreign_key = 'foreign.value';
}
