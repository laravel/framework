<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;

class TestCaseSetUpTraitTest extends TestCase
{
    use TestTrait;

    public $setUpTraitWasCalled = false;

    public function createApplication()
    {
        return new Application();
    }

    public function testSetUpTrait()
    {
        $this->assertTrue($this->setUpTraitWasCalled);
    }
}

trait TestTrait
{
    public function setUpTestTrait()
    {
        $this->setUpTraitWasCalled = true;
    }
}
