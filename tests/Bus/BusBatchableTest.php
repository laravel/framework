<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\BatchRepository;
use Illuminate\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BusBatchableTest extends TestCase
{
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container;
        Container::setInstance($this->container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        m::close();
        parent::tearDown();
    }

    public function test_batch_may_be_retrieved()
    {
        $class = new class
        {
            use Batchable;
        };

        $this->assertSame($class, $class->withBatchId('test-batch-id'));
        $this->assertSame('test-batch-id', $class->batchId);

        $repository = m::mock(BatchRepository::class);
        $repository->shouldReceive('find')->once()->with('test-batch-id')->andReturn('test-batch');
        $this->container->instance(BatchRepository::class, $repository);

        $this->assertSame('test-batch', $class->batch());
    }
}
