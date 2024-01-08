<?php

namespace Illuminate\Tests\Refine;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Refine\RefineQuery;
use Illuminate\Refine\Refiner;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueryRefinerTest extends TestCase
{
    protected function setUp(): void
    {
        Model::setConnectionResolver($this->getMockConnectionResolver());
    }

    protected function tearDown(): void
    {
        m::close();
        Model::unsetConnectionResolver();
        RefineQuery::unsetResolver();
    }

    protected function getMockContainer()
    {
        return m::mock(Container::class);
    }

    protected function getMockQueryBuilder()
    {
        $mock = m::mock(Builder::class);
        $mock->shouldReceive('from')->with(m::type('string'))->andReturnSelf();

        return $mock;
    }

    protected function getMockConnection()
    {
        $mock = m::mock(ConnectionInterface::class);
        $mock->shouldReceive('query')->andReturn($this->getMockQueryBuilder());

        return $mock;
    }

    protected function getMockConnectionResolver()
    {
        $mock = m::mock(ConnectionResolverInterface::class);
        $mock->shouldReceive('connection')->andReturn($this->getMockConnection());

        return $mock;
    }

    /**
     * @return \Illuminate\Database\Query\Builder|\Mockery\Mock
     */
    protected function mockRequestWithBuilder(array $data, Model $model, string $refiner)
    {
        $request = new Request($data);

        $container = $this->getMockContainer();
        $container->shouldReceive('make')->with($refiner)->andReturn(new $refiner);
        $container->shouldReceive('make')->with('request')->andReturn($request);
        RefineQuery::setResolver($container);

        return $model->getConnection()->query();
    }

    public function testCallsMatchedMethod()
    {
        $model = new MockModel();

        $query = $this->mockRequestWithBuilder(['foo' => 1, 'bar' => 2], $model, MockRefiner::class);

        $query->shouldReceive('where')->with('foo', 1)->andReturnSelf();
        $query->shouldReceive('where')->with('bar', 4)->andReturnSelf();
        $query->shouldNotReceive('where')->with('quz', m::type('int'));

        $model->newQuery()->refineBy(MockRefiner::class);
    }

    public function testCallsBeforeAndAfterMethod()
    {
        $model = new MockModel();

        $query = $this->mockRequestWithBuilder(['foo' => 1], $model, MockRefinerAfterBefore::class);

        $query->shouldReceive('where')->with('foo', 1)->andReturnSelf();
        $query->shouldReceive('where')->with('after', true)->andReturnSelf();
        $query->shouldReceive('where')->with('before', true)->andReturnSelf();
        $query->shouldNotReceive('where')->with('bar', m::type('int'));
        $query->shouldNotReceive('where')->with('quz', m::type('int'));

        $model->newQuery()->refineBy(MockRefinerAfterBefore::class);
    }

    public function testUsesRuntimeData()
    {
        $model = new MockModel();

        $query = $this->mockRequestWithBuilder(['foo' => 1, 'bar' => 2], $model, MockRefiner::class);

        $query->shouldNotReceive('where')->with('foo', 2);
        $query->shouldReceive('where')->with('bar', 7)->andReturnSelf();

        $model->newQuery()->refineBy(MockRefiner::class, ['bar' => 5]);
    }

    public function testUsesCustomKeys()
    {
        $model = new MockModel();

        $query = $this->mockRequestWithBuilder(['foo' => 1, 'bar' => 2], $model, MockKeysRefiner::class);

        $query->shouldNotReceive('where')->with('foo', 1);
        $query->shouldReceive('where')->with('bar', 4)->andReturnSelf();

        $model->newQuery()->refineBy(MockKeysRefiner::class);
    }
}

class MockModel extends Model
{
}

class MockRefiner extends Refiner
{
    public function foo($builder, $value)
    {
        $builder->where('foo', $value);
    }

    public function bar($builder, $value, $request)
    {
        $builder->where('bar', $request->get('bar') + $value);
    }

    public function quz($builder, $value)
    {
        $builder->where('quz', $value);
    }
}

class MockRefinerAfterBefore extends MockRefiner
{
    public function after(EloquentBuilder $query, Request $request): void
    {
        $query->where('after', true);
    }

    public function before(EloquentBuilder $query, Request $request): void
    {
        $query->where('before', true);
    }
}

class MockKeysRefiner extends MockRefiner
{
    public function keys(Request $request): array
    {
        return ['bar'];
    }
}
