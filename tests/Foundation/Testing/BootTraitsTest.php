<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\Attributes\SetUp;
use Illuminate\Foundation\Testing\Attributes\TearDown;
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

trait TestTraitWithAttributes
{
    public $attributeSetUp = false;
    public $attributeTearDown = false;

    #[SetUp]
    public function initializeSearch()
    {
        $this->attributeSetUp = true;
    }

    #[TearDown]
    public function cleanUpSearch()
    {
        $this->attributeTearDown = true;
    }
}

class TestCaseWithTrait extends FoundationTestCase
{
    use CreatesApplication;
    use TestTrait;
}

class TestCaseWithAttributeTrait extends FoundationTestCase
{
    use CreatesApplication;
    use TestTraitWithAttributes;
}

class TestCaseWithBothTraits extends FoundationTestCase
{
    use CreatesApplication;
    use TestTrait;
    use TestTraitWithAttributes;
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

    public function testSetUpAndTearDownWithAttributes()
    {
        $testCase = new TestCaseWithAttributeTrait('foo');

        $method = new ReflectionMethod($testCase, 'setUpTraits');
        $method->invoke($testCase);

        $this->assertTrue($testCase->attributeSetUp);

        $method = new ReflectionMethod($testCase, 'callBeforeApplicationDestroyedCallbacks');
        $method->invoke($testCase);

        $this->assertTrue($testCase->attributeTearDown);
    }

    public function testConventionalAndAttributeTraitsWorkTogether()
    {
        $testCase = new TestCaseWithBothTraits('foo');

        $method = new ReflectionMethod($testCase, 'setUpTraits');
        $method->invoke($testCase);

        $this->assertTrue($testCase->setUp);
        $this->assertTrue($testCase->attributeSetUp);

        $method = new ReflectionMethod($testCase, 'callBeforeApplicationDestroyedCallbacks');
        $method->invoke($testCase);

        $this->assertTrue($testCase->tearDown);
        $this->assertTrue($testCase->attributeTearDown);
    }
}
