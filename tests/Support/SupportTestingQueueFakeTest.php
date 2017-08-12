<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Testing\Fakes\QueueFake;
use PHPUnit\Framework\ExpectationFailedException;

class QueueFakeTest extends TestCase
{
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
        } catch (ExpectationFailedException $exception) {
            $this->assertEquals('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.
Failed asserting that false is true.', $exception->getMessage());
        }

        $this->fake->push($this->job);

        $this->fake->assertPushed(JobStub::class);
    }

    public function testAssertNotPushed()
    {
        $this->fake->assertNotPushed(JobStub::class);

        $this->fake->push($this->job);

        try {
            $this->fake->assertNotPushed(JobStub::class);
        } catch (ExpectationFailedException $exception) {
            $this->assertEquals('The unexpected [Illuminate\Tests\Support\JobStub] job was pushed.
Failed asserting that false is true.', $exception->getMessage());
        }
    }

    public function testAssertPushedOn()
    {
        $this->fake->push($this->job, '', 'foo');

        try {
            $this->fake->assertPushedOn('bar', JobStub::class);
        } catch (ExpectationFailedException $exception) {
            $this->assertEquals('The expected [Illuminate\Tests\Support\JobStub] job was not pushed.
Failed asserting that false is true.', $exception->getMessage());
        }

        $this->fake->assertPushedOn('foo', JobStub::class);
    }

    public function testAssertPushedTimes()
    {
        $this->fake->push($this->job);
        $this->fake->push($this->job);

        try {
            $this->fake->assertPushed(JobStub::class, 1);
        } catch (ExpectationFailedException $exception) {
            $this->assertEquals('The expected [Illuminate\Tests\Support\JobStub] job was pushed 2 times instead of 1 times.
Failed asserting that false is true.', $exception->getMessage());
        }

        $this->fake->assertPushed(JobStub::class, 2);
    }
}

class JobStub
{
    public function handle()
    {
        //
    }
}
