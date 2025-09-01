<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Illuminate\Testing\Attributes\SetUp;
use Illuminate\Testing\Attributes\TearDown;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

trait TestTrait
{
    // Integers to ensure methods are called exactly once
    public int $setUp = 0;
    public int $setUpWithAttribute = 0;
    public int $tearDown = 0;
    public int $tearDownWithAttribute = 0;

    public function setUpTestTrait()
    {
        $this->setUp++;
    }

    #[SetUp]
    protected function setUpTestTraitWithAttribute()
    {
        $this->setUpWithAttribute++;
    }

    public function tearDownTestTrait()
    {
        $this->tearDown++;
    }

    #[TearDown]
    public function tearDownTestTraitWithAttribute()
    {
        $this->tearDownWithAttribute++;
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

        $this->assertSame(1, $testCase->setUp);
        $this->assertSame(1, $testCase->setUpWithAttribute);

        $method = new ReflectionMethod($testCase, 'callBeforeApplicationDestroyedCallbacks');
        $method->invoke($testCase);

        $this->assertSame(1, $testCase->tearDown);
        $this->assertSame(1, $testCase->tearDownWithAttribute);
    }
}
