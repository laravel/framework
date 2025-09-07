<?php

namespace Illuminate\Tests\Support;

use BadMethodCallException;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Application;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mockery as m;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SupportTestingQueueFakeTest extends TestCase
{
    /**
     * @var \Illuminate\Support\Testing\Fakes\QueueFake
     */
    private $fake;

    /**
     * @var \Illuminate\Tests\Support\JobStub
     */
    private $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = new QueueFake(new Application);
        $this->job = new JobStub;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testAssertPushed()
    {
        try {
            $this->fake->assertPushed(JobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.', $e->getMessage());
        }

        $this->fake->push($this->job);

        $this->fake->assertPushed(JobStub::class);
    }

    public function testItCanAssertAgainstDataWithPush()
    {
        $data = null;
        $this->fake->push(JobStub::class, ['foo' => 'bar'], 'redis');

        $this->fake->assertPushed(JobStub::class, function ($job, $queue, $jobData) use (&$data) {
            $data = $jobData;

            return true;
        });

        $this->assertSame(['foo' => 'bar'], $data);
    }

    public function testAssertPushedWithIgnore()
    {
        $job = new JobStub;

        $manager = m::mock(QueueManager::class);
        $manager->shouldReceive('push')->once()->withArgs(function ($passedJob) use ($job) {
            return $passedJob === $job;
        });

        $fake = new QueueFake(new Application, JobToFakeStub::class, $manager);

        $fake->push($job);
        $fake->push(new JobToFakeStub());

        $fake->assertNotPushed(JobStub::class);
        $fake->assertPushed(JobToFakeStub::class);
    }

    public function testAssertPushedWithClosure()
    {
        $this->fake->push($this->job);

        $this->fake->assertPushed(function (JobStub $job) {
            return true;
        });
    }

    public function testQueueSize()
    {
        $this->assertEquals(0, $this->fake->size());

        $this->fake->push($this->job);

        $this->assertEquals(1, $this->fake->size());
    }

    public function testAssertNotPushed()
    {
        $this->fake->push($this->job);

        try {
            $this->fake->assertNotPushed(JobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The unexpected [Illuminate\Tests\Support\JobStub] job was pushed.', $e->getMessage());
        }
    }

    public function testAssertNotPushedWithClosure()
    {
        $this->fake->assertNotPushed(JobStub::class);

        $this->fake->push($this->job);

        try {
            $this->fake->assertNotPushed(function (JobStub $job) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The unexpected [Illuminate\Tests\Support\JobStub] job was pushed.', $e->getMessage());
        }
    }

    public function testAssertPushedOn()
    {
        $this->fake->push($this->job, '', 'foo');

        try {
            $this->fake->assertPushedOn('bar', JobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.', $e->getMessage());
        }

        $this->fake->assertPushedOn('foo', JobStub::class);
    }

    public function testAssertPushedOnWithClosure()
    {
        $this->fake->push($this->job, '', 'foo');

        try {
            $this->fake->assertPushedOn('bar', function (JobStub $job) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.', $e->getMessage());
        }

        $this->fake->assertPushedOn('foo', function (JobStub $job) {
            return true;
        });
    }

    public function testAssertPushedTimes()
    {
        $this->fake->push($this->job);
        $this->fake->push($this->job);

        try {
            $this->fake->assertPushed(JobStub::class, 1);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\JobStub] job was pushed 2 times instead of 1 time.', $e->getMessage());
        }

        $this->fake->assertPushed(JobStub::class, 2);
    }

    public function testAssertCount()
    {
        $this->fake->push(function () {
            // Do nothing
        });

        $this->fake->push($this->job);
        $this->fake->push($this->job);

        $this->fake->assertCount(3);
    }

    public function testAssertNothingPushed()
    {
        $this->fake->assertNothingPushed();

        $this->fake->push($this->job);

        $this->fake->push(function () {
            //
        });

        try {
            $this->fake->assertNothingPushed();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The following jobs were pushed unexpectedly', $e->getMessage());
            $this->assertStringContainsString(get_class($this->job), $e->getMessage());
            $this->assertStringContainsString(CallQueuedClosure::class, $e->getMessage());
        }
    }

    public function testAssertPushedUsingBulk()
    {
        $this->fake->assertNothingPushed();

        $queue = 'my-test-queue';
        $this->fake->bulk([
            $this->job,
            new JobStub,
        ], null, $queue);

        $this->fake->assertPushedOn($queue, JobStub::class);
        $this->fake->assertPushed(JobStub::class, 2);
    }

    public function testAssertPushedWithChainUsingClassesOrObjectsArray()
    {
        $this->fake->push(new JobWithChainStub([
            new JobStub,
        ]));

        $this->fake->assertPushedWithChain(JobWithChainStub::class, [
            JobStub::class,
        ]);

        $this->fake->assertPushedWithChain(JobWithChainStub::class, [
            new JobStub,
        ]);
    }

    public function testAssertPushedWithoutChain()
    {
        $this->fake->push(new JobWithChainStub([]));

        $this->fake->assertPushedWithoutChain(JobWithChainStub::class);
    }

    public function testAssertPushedWithChainSameJobDifferentChains()
    {
        $this->fake->push(new JobWithChainStub([
            new JobStub,
        ]));
        $this->fake->push(new JobWithChainStub([
            new JobStub,
            new JobStub,
        ]));

        $this->fake->assertPushedWithChain(JobWithChainStub::class, [
            JobStub::class,
        ]);

        $this->fake->assertPushedWithChain(JobWithChainStub::class, [
            JobStub::class,
            JobStub::class,
        ]);
    }

    public function testAssertPushedWithChainUsingCallback()
    {
        $this->fake->push(new JobWithChainAndParameterStub('first', [
            new JobStub,
            new JobStub,
        ]));

        $this->fake->push(new JobWithChainAndParameterStub('second', [
            new JobStub,
        ]));

        $this->fake->assertPushedWithChain(JobWithChainAndParameterStub::class, [
            JobStub::class,
        ], function ($job) {
            return $job->parameter === 'second';
        });

        try {
            $this->fake->assertPushedWithChain(JobWithChainAndParameterStub::class, [
                JobStub::class,
                JobStub::class,
            ], function ($job) {
                return $job->parameter === 'second';
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected chain was not pushed.', $e->getMessage());
        }
    }

    public function testAssertPushedWithChainErrorHandling()
    {
        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, []);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected [Illuminate\Tests\Support\JobWithChainStub] job was not pushed.', $e->getMessage());
        }

        $this->fake->push(new JobWithChainStub([
            new JobStub,
        ]));

        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, []);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected chain can not be empty.', $e->getMessage());
        }

        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, [
                new JobStub,
                new JobStub,
            ]);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected chain was not pushed.', $e->getMessage());
        }

        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, [
                JobStub::class,
                JobStub::class,
            ]);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected chain was not pushed.', $e->getMessage());
        }
    }

    public function testCallUndefinedMethodErrorHandling()
    {
        try {
            $this->fake->undefinedMethod();
        } catch (BadMethodCallException $e) {
            $this->assertSame(sprintf(
                'Call to undefined method %s::%s()', get_class($this->fake), 'undefinedMethod'
            ), $e->getMessage());
        }
    }

    public function testAssertClosurePushed()
    {
        $this->fake->push(function () {
            // Do nothing
        });

        $this->fake->assertClosurePushed();
    }

    public function testAssertClosurePushedWithTimes()
    {
        $this->fake->push(function () {
            // Do nothing
        });

        $this->fake->push(function () {
            // Do nothing
        });

        $this->fake->assertClosurePushed(2);
    }

    public function testAssertClosureNotPushed()
    {
        $this->fake->push($this->job);

        $this->fake->assertClosureNotPushed();
    }

    public function testItDoesntFakeJobsPassedViaExcept()
    {
        $job = new JobStub;

        $manager = m::mock(QueueManager::class);
        $manager->shouldReceive('push')->once()->withArgs(function ($passedJob) use ($job) {
            return $passedJob === $job;
        });

        $fake = (new QueueFake(new Application, [], $manager))->except(JobStub::class);

        $fake->push($job);
        $fake->push(new JobToFakeStub());

        $fake->assertNotPushed(JobStub::class);
        $fake->assertPushed(JobToFakeStub::class);
    }

    public function testItCanSerializeAndRestoreJobs()
    {
        // confirm that the default behavior is maintained
        $this->fake->push(new JobWithSerialization('hello'));
        $this->fake->assertPushed(JobWithSerialization::class, fn ($job) => $job->value === 'hello');

        $job = new JobWithSerialization('hello');

        $fake = new QueueFake(new Application);
        $fake->serializeAndRestore();
        $fake->push($job);

        $fake->assertPushed(
            JobWithSerialization::class,
            fn ($job) => $job->value === 'hello-serialized-unserialized'
        );
    }

    public function testItCanFakePushedJobsWithClassAndPayload()
    {
        $fake = new QueueFake(new Application, ['JobStub']);

        $this->assertTrue($fake->shouldFakeJob('JobStub'));

        $fake->push('JobStub', ['job' => 'payload']);

        $fake->assertPushed('JobStub');
        $fake->assertPushed('JobStub', 1);
        $fake->assertPushed('JobStub', fn ($job, $queue, $payload) => $payload === ['job' => 'payload']);
    }

    public function testAssertChainUsingClassesOrObjectsArray()
    {
        $job = new JobWithChainStub([
            new JobStub,
        ]);

        $job->assertHasChain([
            JobStub::class,
        ]);

        $job->assertHasChain([
            new JobStub(),
        ]);
    }

    public function testAssertNoChain()
    {
        $job = new JobWithChainStub([]);

        $job->assertDoesntHaveChain();
    }

    public function testAssertChainErrorHandling()
    {
        $job = new JobWithChainStub([
            new JobStub,
        ]);

        try {
            $job->assertHasChain([]);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The expected chain can not be empty.', $e->getMessage());
        }

        try {
            $job->assertHasChain([
                new JobStub,
                new JobStub,
            ]);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The job does not have the expected chain.', $e->getMessage());
        }

        try {
            $job->assertHasChain([
                JobStub::class,
                JobStub::class,
            ]);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The job does not have the expected chain.', $e->getMessage());
        }

        try {
            $job->assertDoesntHaveChain();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertStringContainsString('The job has chained jobs.', $e->getMessage());
        }
    }

    public function testGetRawPushes()
    {
        $this->fake->pushRaw('some-payload', null, ['options' => 'yeah']);
        $this->fake->pushRaw('some-other-payload', 'my-queue', ['options' => 'also yeah']);

        $actualPushedRaw = $this->fake->rawPushes();

        $this->assertEqualsCanonicalizing([
            ['payload' => 'some-payload', 'queue' => null, 'options' => ['options' => 'yeah']],
            ['payload' => 'some-other-payload', 'queue' => 'my-queue', 'options' => ['options' => 'also yeah']],
        ], $actualPushedRaw);
    }

    public function testPushedRaw()
    {
        $this->fake->pushRaw('some-payload', null, ['options' => 'yeah']);
        $this->fake->pushRaw('some-other-payload', 'my-queue', ['options' => 'also yeah']);

        $this->assertCount(2, $this->fake->pushedRaw());

        $pushedRaw = $this->fake->pushedRaw(fn ($payload) => $payload === 'some-payload');
        $this->assertCount(1, $pushedRaw);
        $this->assertEqualsCanonicalizing(
            ['payload' => 'some-payload', 'queue' => null, 'options' => ['options' => 'yeah']],
            $pushedRaw[0]
        );

        $pushedRaw = $this->fake->pushedRaw(
            fn ($payload, $queue, $options) => $payload === 'some-other-payload'
                && $queue === 'my-queue'
                && $options['options'] === 'also yeah'
        );
        $this->assertCount(1, $pushedRaw);

        $pushedRaw = $this->fake->pushedRaw(fn ($payload, $queue, $options) => $options === []);
        $this->assertCount(0, $pushedRaw);
    }
}

class JobStub
{
    public function handle()
    {
        //
    }
}

class JobToFakeStub
{
    public function handle()
    {
        //
    }
}

class JobWithChainStub
{
    use Queueable;

    public function __construct($chain)
    {
        $this->chain($chain);
    }

    public function handle()
    {
        //
    }
}

class JobWithChainAndParameterStub
{
    use Queueable;

    public $parameter;

    public function __construct($parameter, $chain)
    {
        $this->parameter = $parameter;
        $this->chain($chain);
    }

    public function handle()
    {
        //
    }
}

class JobWithSerialization
{
    use Queueable;

    public function __construct(public $value)
    {
    }

    public function __serialize(): array
    {
        return ['value' => $this->value.'-serialized'];
    }

    public function __unserialize(array $data): void
    {
        $this->value = $data['value'].'-unserialized';
    }
}
