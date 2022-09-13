<?php

namespace Illuminate\Tests\Support;

use BadMethodCallException;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Application;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mockery as m;
use PHPUnit\Framework\Constraint\ExceptionMessage;
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
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.'));
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
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\JobStub] job was pushed.'));
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
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\JobStub] job was pushed.'));
        }
    }

    public function testAssertPushedOn()
    {
        $this->fake->push($this->job, '', 'foo');

        try {
            $this->fake->assertPushedOn('bar', JobStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.'));
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
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.'));
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
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\JobStub] job was pushed 2 times instead of 1 times.'));
        }

        $this->fake->assertPushed(JobStub::class, 2);
    }

    public function testAssertNothingPushed()
    {
        $this->fake->assertNothingPushed();

        $this->fake->push($this->job);

        try {
            $this->fake->assertNothingPushed();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Jobs were pushed unexpectedly.'));
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
            $this->assertThat($e, new ExceptionMessage('The expected chain was not pushed'));
        }
    }

    public function testAssertPushedWithChainErrorHandling()
    {
        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, []);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\JobWithChainStub] job was not pushed'));
        }

        $this->fake->push(new JobWithChainStub([
            new JobStub,
        ]));

        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, []);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected chain can not be empty'));
        }

        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, [
                new JobStub,
                new JobStub,
            ]);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected chain was not pushed'));
        }

        try {
            $this->fake->assertPushedWithChain(JobWithChainStub::class, [
                JobStub::class,
                JobStub::class,
            ]);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected chain was not pushed'));
        }
    }

    public function testCallUndefinedMethodErrorHandling()
    {
        try {
            $this->fake->undefinedMethod();
        } catch (BadMethodCallException $e) {
            $this->assertThat($e, new ExceptionMessage(sprintf(
                'Call to undefined method %s::%s()', get_class($this->fake), 'undefinedMethod'
            )));
        }
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
