<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

trait TestTrait
{
    public $setUp = false;
    public $tearDown = false;

    public function setUpTestTrait()
    {
        $this->setUp = true;
    }

    public function tearDownTestTrait()
    {
        $this->tearDown = true;
    }
}

class TestCaseWithTrait extends FoundationTestCase
{
    use CreatesApplication;
    use TestTrait;
}

class BootTraitsTest extends TestCase
{
    public function testSetUpAndTearDownTraits()
    {
        $testCase = new TestCaseWithTrait('foo');

        $method = new ReflectionMethod($testCase, 'setUpTraits');
        $method->invoke($testCase);

        $this->assertTrue($testCase->setUp);

        $method = new ReflectionMethod($testCase, 'callBeforeApplicationDestroyedCallbacks');
        $method->invoke($testCase);

        $this->assertTrue($testCase->tearDown);
    }
}
