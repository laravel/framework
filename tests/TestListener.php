<?php

namespace Illuminate\Tests;

use Mockery as m;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener as BaseTestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

class TestListener implements BaseTestListener
{
    use TestListenerDefaultImplementation;

    public function endTest(Test $test, float $time): void
    {
        m::close();
    }
}
