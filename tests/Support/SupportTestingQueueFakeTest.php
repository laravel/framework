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

    public function testAssertPushed()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\JobStub] job was not pushed.');

        $this->fake->assertPushed(JobStub::class);

        $this->fake->push($this->job);

        $this->fake->assertPushed(JobStub::class);
    }

    public function testAssertNotPushed()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The unexpected [Illuminate\\Tests\\Support\\JobStub] job was pushed.');

        $this->fake->assertNotPushed(JobStub::class);

        $this->fake->push($this->job);

        $this->fake->assertNotPushed(JobStub::class);
    }

    public function testAssertPushedOn()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\JobStub] job was not pushed.');

        $this->fake->push($this->job, '', 'foo');

        $this->fake->assertPushedOn('bar', JobStub::class);

        $this->fake->assertPushedOn('foo', JobStub::class);
    }

    public function testAssertPushedTimes()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\JobStub] job was pushed 2 times instead of 1 times.');

        $this->fake->push($this->job);
        $this->fake->push($this->job);

        $this->fake->assertPushed(JobStub::class, 1);

        $this->fake->assertPushed(JobStub::class, 2);
    }

    public function testAssertNothingPushed()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('Jobs were pushed unexpectedly.');

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
