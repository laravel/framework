<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasNone;

class DatabaseEloquentHasNoneTest extends TestCase
{
    protected $builder;

    protected $model;

    public function tearDown()
    {
        m::close();
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    public function testModelsAreReturnedWithoutMatches()
    {
        $relation = $this->getRelation();

        $model1 = new EloquentHasNoneModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasNoneModelStub;
        $model2->id = 2;

        $models = $relation->match([$model1, $model2], new Collection, 'foo');

        $this->assertEquals([$model1, $model2], $models);
    }

    protected function getRelation()
    {
        $this->model = m::mock('Illuminate\Database\Eloquent\Model');
        $this->builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $this->builder->shouldReceive('getModel')->andReturn($this->model);

        return new HasNone($this->builder, $this->model);
    }
}

class EloquentHasNoneModelStub extends \Illuminate\Database\Eloquent\Model
{
    //
}
