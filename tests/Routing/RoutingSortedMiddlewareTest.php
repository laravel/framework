<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\SortedMiddleware;
use PHPUnit\Framework\TestCase;

class RoutingSortedMiddlewareTest extends TestCase
{
    public function testMiddlewareCanBeSortedByPriority()
    {
        $priority = [
            'First',
            'Second',
            'Third',
        ];

        $middleware = [
            'Something',
            'Something',
            'Something',
            'Something',
            'Second',
            'Otherthing',
            'First:api',
            'Third:foo',
            'First:foo,bar',
            'Third',
            'Second',
        ];

        $expected = [
            'Something',
            'First:api',
            'First:foo,bar',
            'Second',
            'Otherthing',
            'Third:foo',
            'Third',
        ];

        $this->assertSame($expected, (new SortedMiddleware($priority, $middleware))->all());

        $this->assertSame([], (new SortedMiddleware(['First'], []))->all());
        $this->assertSame(['First'], (new SortedMiddleware(['First'], ['First']))->all());
        $this->assertSame(['First', 'Second'], (new SortedMiddleware(['First', 'Second'], ['Second', 'First']))->all());
    }

    public function testItDoesNotMoveNonStringValues()
    {
        $closure = function () {
            return 'foo';
        };

        $closure2 = function () {
            return 'bar';
        };

        $this->assertSame([2, 1], (new SortedMiddleware([1, 2], [2, 1]))->all());
        $this->assertSame(['Second', $closure], (new SortedMiddleware(['First', 'Second'], ['Second', $closure]))->all());
        $this->assertSame(['a', 'b', $closure], (new SortedMiddleware(['a', 'b'], ['b', $closure, 'a']))->all());
        $this->assertSame([$closure2, 'a', 'b', $closure, 'foo'], (new SortedMiddleware(['a', 'b'], [$closure2, 'b', $closure, 'a', 'foo']))->all());
        $this->assertSame([$closure, 'a', 'b', $closure2, 'foo'], (new SortedMiddleware(['a', 'b'], [$closure, 'b', $closure2, 'foo', 'a']))->all());
        $this->assertSame(['a', $closure, 'b', $closure2, 'foo'], (new SortedMiddleware(['a', 'b'], ['a', $closure, 'b', $closure2, 'foo']))->all());
        $this->assertSame([$closure, $closure2, 'foo', 'a'], (new SortedMiddleware(['a', 'b'], [$closure, $closure2, 'foo', 'a']))->all());
    }

    public function testItSortsUsingParentsAndContracts()
    {
        $priority = [
            FirstContractStub::class,
            SecondStub::class,
            'Third',
        ];

        $middleware = [
            'Something',
            'Something',
            'Something',
            'Something',
            SecondChildStub::class,
            'Otherthing',
            FirstStub::class.':api',
            'Third:foo',
            FirstStub::class.':foo,bar',
            'Third',
            SecondChildStub::class,
        ];

        $expected = [
            'Something',
            FirstStub::class.':api',
            FirstStub::class.':foo,bar',
            SecondChildStub::class,
            'Otherthing',
            'Third:foo',
            'Third',
        ];

        $this->assertSame($expected, (new SortedMiddleware($priority, $middleware))->all());
    }
}

interface FirstContractStub
{
    //
}

class FirstStub implements FirstContractStub
{
    //
}

class SecondStub
{
    //
}

class SecondChildStub extends SecondStub
{
    //
}
