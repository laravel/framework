<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Tests\AbstractTestCase as TestCase;

class FoundationEnvironmentDetectorTest extends TestCase
{
    public function testClosureCanBeUsedForCustomEnvironmentDetection()
    {
        $env = new \Illuminate\Foundation\EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        });
        $this->assertEquals('foobar', $result);
    }

    public function testConsoleEnvironmentDetection()
    {
        $env = new \Illuminate\Foundation\EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--env=local']);
        $this->assertEquals('local', $result);
    }
}
