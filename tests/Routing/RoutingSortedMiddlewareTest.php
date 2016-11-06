<?php

use Illuminate\Routing\SortedMiddleware;

class RoutingSortedMiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testMiddlewareCanBeSortedByPriority()
    {
        $priority = [
            'First',
            'Second',
            'BeforeThird',
            'Third',
        ];

        $closure = function () {
        };

        $anotherClosure = function () {
        };

        $middleware = [
            'Something',
            'Something',
            'Something',
            'Something',
            $closure,
            $anotherClosure,
            'Second',
            'Otherthing',
            'First:api',
            'Third:foo',
            'First:foo,bar',
            'Third',
            'Second',
            'BeforeThird',
        ];

        $expected = [
            'Something',
            $closure,
            $anotherClosure,
            'First:api',
            'First:foo,bar',
            'Second',
            'Otherthing',
            'BeforeThird',
            'Third:foo',
            'Third',
        ];

        $this->assertEquals($expected, (new SortedMiddleware($middleware))->sortMiddleware($priority)->all());

        $this->assertEquals([], (new SortedMiddleware())->sortMiddleware(['First'])->all());
        $this->assertEquals(['First'], (new SortedMiddleware(['First']))->sortMiddleware(['First'])->all());
        $this->assertEquals(['First', 'Second'], (new SortedMiddleware(['Second', 'First']))->sortMiddleware(['First', 'Second'])->all());

        $this->assertEquals(['Second', $closure], (new SortedMiddleware(['Second', $closure]))->sortMiddleware(['First', 'Second'])->all());
    }
}
