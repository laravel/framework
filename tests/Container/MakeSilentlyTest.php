<?php

namespace Illuminate\Tests\Container;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class MakeSilentlyTest extends TestCase
{
    public function testMakeSilentlyForStringAbstract()
    {
        $container = new Container;

        $container->bind('foo', MadeSilent::class);

        $callCounter = 0;
        $container->resolving('foo', function () use (&$callCounter) {
            $callCounter++;
        });

        $container->afterResolving('foo', function () use (&$callCounter) {
            $callCounter++;
        });

        $container->resolving(function () use (&$callCounter) {
            $callCounter++;
        });

        $container->makeSilently('foo');
        $container->makeSilently(MadeSilent::class);
        $this->assertEquals(0, $callCounter);
    }

    public function testMakeSilentlyForConcretes()
    {
        $container = new Container;

        $callCounter = 0;
        $container->resolving(MadeSilent::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->afterResolving(MadeSilent::class, function () use (&$callCounter) {
            $callCounter++;
        });

        $container->resolving(function () use (&$callCounter) {
            $callCounter++;
        });

        $container->makeSilently(MadeSilent::class);
        $this->assertEquals(0, $callCounter);
    }
}

class MadeSilent
{
}
