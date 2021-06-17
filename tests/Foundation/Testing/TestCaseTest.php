<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\TestCase;

class TestCaseTest extends TestCase
{
    public function testTestCaseTraitesSetUpped()
    {
        $test = new TestCaseWithTrait();

        $test->run();

        $this->assertTrue($test->fooBarIsSetUpped);
    }
}

trait FooBarTrait
{
    public $fooBarIsSetUpped = false;

    public function setUpFooBarTrait()
    {
        $this->fooBarIsSetUpped = true;
    }
}

class TestCaseWithTrait extends FoundationTestCase
{
    use CreatesApplication;
    use FooBarTrait;

    public $testSomething = false;

    public function testSomething(): void
    {
        $this->testSomething = true;
    }
}
