<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\EnvironmentDetector;

class FoundationEnvironmentDetectorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testClosureCanBeUsedForCustomEnvironmentDetection()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        });
        $this->assertEquals('foobar', $result);
    }

    public function testConsoleEnvironmentDetection()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--env=local']);
        $this->assertEquals('local', $result);
    }
}
