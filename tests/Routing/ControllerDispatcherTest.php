<?php

namespace Illuminate\Tests\Routing;

use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Routing\ControllerDispatcher;

class ControllerDispatcherTest extends TestCase
{
    /**
     * @var \Illuminate\Routing\ControllerDispatcher
     */
    protected $controllerDispatcher;

    public function setUp()
    {
        parent::setUp();

        $container = new Container();
        $this->controllerDispatcher = new ControllerDispatcher($container);
    }

    public function targetResolveMethodDependenciesKeepKeys($first)
    {
    }

    public function testResolveMethodDependenciesKeepKeys()
    {
        $parameters = [
            'first' => 'first-value',
        ];
        $target = new ReflectionMethod(self::class, 'targetResolveMethodDependenciesKeepKeys');

        $actual = $this->controllerDispatcher->resolveMethodDependencies($parameters, $target);

        $expected = [
            'first' => 'first-value',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function targetResolveMethodDependenciesKeepsOrderOfKeysAndValues($first, $second)
    {
    }

    public function testResolveMethodDependenciesKeepsOrderOfKeysAndValues()
    {
        $parameters = [
            'second' => 'second-value',
            'first' => 'first-value',
        ];
        $target = new ReflectionMethod(self::class, 'targetResolveMethodDependenciesKeepsOrderOfKeysAndValues');

        $actual = $this->controllerDispatcher->resolveMethodDependencies($parameters, $target);

        $expected = [
            'second' => 'second-value',
            'first' => 'first-value',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function targetResolveMethodDependenciesKeepsUnknownKeys($first)
    {
    }

    public function testResolveMethodDependenciesKeepsUnknownKeys()
    {
        $parameters = [
            'first' => 'first-value',
            'unknown' => 'unknown',
        ];
        $target = new ReflectionMethod(self::class, 'targetResolveMethodDependenciesKeepsUnknownKeys');

        $actual = $this->controllerDispatcher->resolveMethodDependencies($parameters, $target);

        $expected = [
            'first' => 'first-value',
            'unknown' => 'unknown',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function targetResolveMethodDependenciesDoesNotCrashOnMissingKeys($first)
    {
    }

    public function testResolveMethodDependenciesDoesNotCrashOnMissingKeys()
    {
        $parameters = [];
        $target = new ReflectionMethod(self::class, 'targetResolveMethodDependenciesDoesNotCrashOnMissingKeys');

        $actual = $this->controllerDispatcher->resolveMethodDependencies($parameters, $target);

        $expected = [];
        $this->assertEquals($expected, $actual);
    }
}
