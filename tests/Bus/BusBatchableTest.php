<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\BatchRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Testing\Fakes\BatchFake;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BusBatchableTest extends TestCase
{
    protected function tearDown(): void {
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

        Container::setInstance($container = new Container);

        $repository = m::mock(BatchRepository::class);
        $repository->shouldReceive('find')->once()->with('test-batch-id')->andReturn('test-batch');
        $container->instance(BatchRepository::class, $repository);

        $this->assertSame('test-batch', $class->batch());

        Container::setInstance(null);
    }

    public function test_with_fake_batch_sets_and_returns_fake()
    {
        $job = new class
        {
            use Batchable;
        };

        [$self, $batch] = $job->withFakeBatch('test-batch-id', 'test-batch-name', 3, 3, 0, [], []);

        $this->assertSame($job, $self);
        $this->assertInstanceOf(BatchFake::class, $batch);
        $this->assertSame($batch, $job->batch());
        $this->assertSame('test-batch-id', $job->batch()->id);
        $this->assertSame('test-batch-name', $job->batch()->name);
        $this->assertSame(3, $job->batch()->totalJobs);
    }

    public function test_batching_reflects_cancelled_state()
    {
        $job = new class
        {
            use Batchable;
        };

        $job->withFakeBatch('test-batch-id', 'test-batch-name');

        // Initially not cancelled
        $this->assertTrue($job->batching());

        // Cancel the batch and ensure batching() returns false
        $job->batch()->cancel();
        $this->assertFalse($job->batching());
    }
}
