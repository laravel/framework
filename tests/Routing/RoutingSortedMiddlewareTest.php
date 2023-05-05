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

        $this->assertEquals($expected, (new SortedMiddleware($priority, $middleware))->all());

        $this->assertEquals([], (new SortedMiddleware(['First'], []))->all());
        $this->assertEquals(['First'], (new SortedMiddleware(['First'], ['First']))->all());
        $this->assertEquals(['First', 'Second'], (new SortedMiddleware(['First', 'Second'], ['Second', 'First']))->all());
    }

    public function testItDoesNotMoveNonStringValues()
    {
        $closure = function () {
            return 'foo';
        };

        $closure2 = function () {
            return 'bar';
        };

        $this->assertEquals([2, 1], (new SortedMiddleware([1, 2], [2, 1]))->all());
        $this->assertEquals(['Second', $closure], (new SortedMiddleware(['First', 'Second'], ['Second', $closure]))->all());
        $this->assertEquals(['a', 'b', $closure], (new SortedMiddleware(['a', 'b'], ['b', $closure, 'a']))->all());
        $this->assertEquals([$closure2, 'a', 'b', $closure, 'foo'], (new SortedMiddleware(['a', 'b'], [$closure2, 'b', $closure, 'a', 'foo']))->all());
        $this->assertEquals([$closure, 'a', 'b', $closure2, 'foo'], (new SortedMiddleware(['a', 'b'], [$closure, 'b', $closure2, 'foo', 'a']))->all());
        $this->assertEquals(['a', $closure, 'b', $closure2, 'foo'], (new SortedMiddleware(['a', 'b'], ['a', $closure, 'b', $closure2, 'foo']))->all());
        $this->assertEquals([$closure, $closure2, 'foo', 'a'], (new SortedMiddleware(['a', 'b'], [$closure, $closure2, 'foo', 'a']))->all());
    }
}
