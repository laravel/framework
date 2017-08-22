<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Testing\Fakes\QueueFake;

class QueueFakeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->fake = new QueueFake(new Application);
        $this->job = new JobStub;
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\JobStub] job was not pushed.
     */
    public function testAssertPushed()
    {
        $this->fake->assertPushed(JobStub::class);

        $this->fake->push($this->job);

        $this->fake->assertPushed(JobStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The unexpected [Illuminate\Tests\Support\JobStub] job was pushed.
     */
    public function testAssertNotPushed()
    {
        $this->fake->assertNotPushed(JobStub::class);

        $this->fake->push($this->job);

        $this->fake->assertNotPushed(JobStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\JobStub] job was not pushed.
     */
    public function testAssertPushedOn()
    {
        $this->fake->push($this->job, '', 'foo');

        $this->fake->assertPushedOn('bar', JobStub::class);

        $this->fake->assertPushedOn('foo', JobStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\JobStub] job was pushed 2 times instead of 1 times.
     */
    public function testAssertPushedTimes()
    {
        $this->fake->push($this->job);
        $this->fake->push($this->job);

        $this->fake->assertPushed(JobStub::class, 1);

        $this->fake->assertPushed(JobStub::class, 2);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage Jobs were pushed unexpectedly.
     */
    public function testAssertNothingPushed()
    {
        $this->fake->assertNothingPushed();

        $this->fake->push($this->job);

        $this->fake->assertNothingPushed();
    }
}

class JobStub
{
    public function handle()
    {
        //
    }
}
