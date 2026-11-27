<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Illuminate\Support\Facades\Facade;
use JMac\Testing\Double;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestStatus\TestStatus;

class InteractsWithTestCaseLifecycleDoubleTest extends TestCase
{
    protected function tearDown(): void
    {
        Facade::useMockery();

        parent::tearDown();
    }

    public function testUnmetFacadeExpectationFailsTheTestWhenUsingDouble()
    {
        Facade::useDouble();

        $test = new LifecycleTestCase('testPlaceholder');
        $test->runSetUp();

        DoubleLifecycleFacade::expects('handle');

        $test->setStatus(TestStatus::success());

        $this->expectException(AssertionFailedError::class);

        $test->runTearDown();
    }

    public function testMetFacadeExpectationPassesWhenUsingDouble()
    {
        Facade::useDouble();

        $test = new LifecycleTestCase('testPlaceholder');
        $test->runSetUp();

        DoubleLifecycleFacade::expects('handle')->returns('handled');

        $this->assertSame('handled', DoubleLifecycleFacade::handle());

        $test->setStatus(TestStatus::success());
        $test->runTearDown();
    }

    public function testFailedTestSkipsVerificationWhenUsingDouble()
    {
        Facade::useDouble();

        $test = new LifecycleTestCase('testPlaceholder');
        $test->runSetUp();

        DoubleLifecycleFacade::expects('handle');

        $test->setStatus(TestStatus::failure('failed'));
        $test->runTearDown();

        $this->assertSame(0, Double::verifyAll());
    }

    public function testAutoVerifyIsUntouchedWhenUsingMockery()
    {
        Double::enableAutoVerify();

        try {
            $test = new LifecycleTestCase('testPlaceholder');
            $test->runSetUp();

            Double::for(DoubleLifecycleService::class)->expects('handle');

            $test->setStatus(TestStatus::success());
            $test->runTearDown();
        } finally {
            $this->expectException(AssertionFailedError::class);
            $this->expectExceptionMessage('it was never called');

            Double::verifyAll();
        }
    }

}

class LifecycleTestCase extends FoundationTestCase
{
    use CreatesApplication;

    public function runSetUp(): void
    {
        $this->setUp();
    }

    public function runTearDown(): void
    {
        $this->tearDown();
    }

    public function testPlaceholder()
    {
        //
    }
}

class DoubleLifecycleFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DoubleLifecycleService::class;
    }
}

class DoubleLifecycleService
{
    public function handle()
    {
        return 'real';
    }
}
