<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\EnvironmentDetector;
use PHPUnit\Framework\TestCase;

class FoundationEnvironmentDetectorTest extends TestCase
{
    public function testClosureCanBeUsedForCustomEnvironmentDetection()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        });
        $this->assertSame('foobar', $result);
    }

    public function testConsoleEnvironmentDetectionEmptyArray()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, []);
        $this->assertSame('foobar', $result);
    }

    public function testConsoleEnvironmentDetection()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--env=local']);
        $this->assertSame('local', $result);
    }

    public function testConsoleEnvironmentDetectionStartWithEnv()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--enviroment=local']);
        $this->assertSame('local', $result);
    }

    public function testConsoleEnvironmentDetectionSeparatedWithSpace()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--env', 'local']);
        $this->assertSame('local', $result);
    }

    public function testConsoleEnvironmentDetectionWithNoValue()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--env']);
        $this->assertSame('foobar', $result);
    }

    public function testConsoleEnvironmentDetectionWithNoValueStartWithDashDashEnv()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--enviroment']);
        $this->assertFalse($result);
    }

    public function testConsoleEnvironmentDetectionNotIncludeDashDashEnv()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['env']);
        $this->assertSame('foobar', $result);
    }

    public function testConsoleEnvironmentDetectionNotFirstArgument()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--help', '--env', 'local']);
        $this->assertSame('local', $result);
    }

    public function testConsoleEnvironmentDetectionPriority()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--env=local', '--enviroment=production']);
        $this->assertSame('local', $result);

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--enviroment=production', '--env=local']);
        $this->assertSame('production', $result);
    }
}
