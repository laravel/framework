<?php

use Mockery as m;

class FoundationEnvironmentDetectorTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testClosureCanBeUsedForCustomEnvironmentDetection()
    {
        $env = new Illuminate\Foundation\EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        });
        $this->assertEquals('foobar', $result);
    }

    public function testConsoleEnvironmentDetection()
    {
        $env = new Illuminate\Foundation\EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--env=local']);
        $this->assertEquals('local', $result);
    }
}
