<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Http\Middleware\TrimStrings;

class TrimStringsTest extends TestCase
{
    public function testTrimSimpleFields()
    {
        $middleware = new TrimStrings;
        $request = new Request(
            [
                'field1' => 'value1',
                'field2' => '  value2  ',
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('value1', $request->get('field1'));
            $this->assertEquals('value2', $request->get('field2'));
        });
    }

    /** @test */
    public function testTrimArrayFields()
    {
        $middleware = new TrimStrings;
        $request = new Request(
            [
                'field1' => ['value1', 'value2'],
                'field2' => ['  value1  ', '  value2  '],
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $t1 = $request->get('field1');
            $this->assertEquals('value1', $t1[0]);
            $this->assertEquals('value2', $t1[1]);

            $t2 = $request->get('field2');
            $this->assertEquals('value1', $t2[0]);
            $this->assertEquals('value2', $t2[1]);
        });
    }

    public function testTrimSimpleFieldsExceptSome()
    {
        $middleware = new TrimStringsExcept;

        $request = new Request(
            [
                'field1' => 'value1',
                'field2' => ' value2 ',
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('value1', $request->get('field1'));
            $this->assertEquals(' value2 ', $request->get('field2'));
        });
    }

    public function testTrimArrayFieldsExceptSome()
    {
        $middleware = new TrimStringsExcept;
        $request = new Request(
            [
                'field1' => ['value1', 'value2'],
                'field2' => ['  value1  ', '  value2  '],
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $t1 = $request->get('field1');
            $this->assertEquals('value1', $t1[0]);
            $this->assertEquals('value2', $t1[1]);

            $t2 = $request->get('field2');
            $this->assertEquals('  value1  ', $t2[0]);
            $this->assertEquals('  value2  ', $t2[1]);
        });
    }

    public function testTrimNestedArrayFieldsExceptSome()
    {
        $middleware = new TrimStringsExcept;
        $request = new Request(
            [
                'nested' => [
                                [
                                    'field1' => ' trimmed ',
                                    'field2' => ' not trimmed ',
                                ],
                            ],
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $t = $request->get('nested');
            $this->assertEquals('trimmed', $t[0]['field1']);
            $this->assertEquals(' not trimmed ', $t[0]['field2']);
        });
    }
}

class TrimStringsExcept extends TrimStrings
{
    protected $except = [
        'field2',
    ];
}
