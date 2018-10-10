<?php

namespace Illuminate\Tests\Support;

use Illuminate\Bus\Queueable;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Testing\Fakes\QueueFake;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\Constraint\ExceptionMessage;

class SupportTestingQueueFakeTest extends TestCase
{
    /**
     * @var QueueFake
     */
    private $fake;

    /**
     * @var JobStub
     */
    private $job;

    protected function setUp()
    {
        parent::setUp();
        $this->fake = new QueueFake(new Application);
        $this->job = new JobStub;
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

    public function testQueueSize()
    {
        $this->assertEquals(0, $this->fake->size());

        $this->fake->push($this->job);

        $this->assertEquals(1, $this->fake->size());
    }

    public function testAssertNotPushed()
    {
        $this->fake->assertNotPushed(JobStub::class);

        $this->fake->push($this->job);

        try {
            $this->fake->assertNotPushed(JobStub::class);
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
            return $job->parameter == 'second';
        });

        try {
            $this->fake->assertPushedWithChain(JobWithChainAndParameterStub::class, [
                JobStub::class,
                JobStub::class,
            ], function ($job) {
                return $job->parameter == 'second';
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
}

class JobStub
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
