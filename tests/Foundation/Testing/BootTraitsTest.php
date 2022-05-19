<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\TestCase;

trait TestTrait
{
    public $booted = false;

    public function setUpTestTrait()
    {
        $this->booted = true;
    }
}

class TestCaseWithTrait extends FoundationTestCase
{
    use CreatesApplication;
    use TestTrait;
}

class BootTraitsTest extends TestCase
{
    public function testSetUpTraitsWithBootMethod()
    {
        $testCase = new TestCaseWithTrait;

        $method = new \ReflectionMethod(get_class($testCase), 'setUpTraits');
        tap($method)->setAccessible(true)->invoke($testCase);

        $this->assertTrue($testCase->booted);
    }
}
