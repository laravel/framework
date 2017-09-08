<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\Concerns\ComposesTemplateMethods;

class FoundationComposesTemplateMethodsTest extends TestCase
{
    use ComposesTemplateMethods, TestTraitOne, TestTraitTwo;

    public function testCallsComposedSetUpMethods()
    {
        $this->callTraitTemplateMethods('setUp');

        $this->assertTrue($this->testTraitOneSetUp);
        $this->assertTrue($this->testTraitTwoSetUp);
    }

    public function testCallsComposedTearDownMethods()
    {
        $this->callTraitTemplateMethods('tearDown');

        $this->assertFalse($this->testTraitOneSetUp);
        $this->assertFalse($this->testTraitTwoSetUp);
    }
}

trait TestTraitOne
{
    public $testTraitOneSetUp;

    public function setUpTestTraitOne()
    {
        $this->testTraitOneSetUp = true;
    }

    public function tearDownTestTraitOne()
    {
        $this->testTraitOneSetUp = false;
    }
}

trait TestTraitTwo
{
    public $testTraitTwoSetUp;

    public function setUpTestTraitTwo()
    {
        $this->testTraitTwoSetUp = true;
    }

    public function tearDownTestTraitTwo()
    {
        $this->testTraitTwoSetUp = false;
    }
}
