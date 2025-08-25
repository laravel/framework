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

    public function testConsoleEnvironmentDetection()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--env=local']);
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

    public function testConsoleEnvironmentDetectionDoesNotUseArgumentThatStartsWithEnv()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--envelope=mail']);
        $this->assertSame('foobar', $result);
    }

    public function testConsoleEnvironmentDetectionDoesNotUseArgumentThatStartsWithEnvSeparatedWithSpace()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--envelope', 'mail']);
        $this->assertSame('foobar', $result);
    }

    public function testConsoleEnvironmentDetectionDoesNotUseArgumentThatStartsWithEnvWithNoValue()
    {
        $env = new EnvironmentDetector;

        $result = $env->detect(function () {
            return 'foobar';
        }, ['--envelope']);
        $this->assertSame('foobar', $result);
    }
}
