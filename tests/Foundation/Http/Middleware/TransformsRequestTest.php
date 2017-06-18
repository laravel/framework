<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Http\Middleware\TransformsRequest;

class TransformsRequestTest extends TestCase
{
    public function testLowerAgeAndAddBeer()
    {
        $middleware = new ManipulateInput;
        $request = new Request(
            [
                'name' => 'Damian',
                'beers' => 4,
            ],
            ['age' => 28]
        );

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('Damian', $request->get('name'));
            $this->assertEquals(27, $request->get('age'));
            $this->assertEquals(5, $request->get('beers'));
        });
    }

    public function testAjaxLowerAgeAndAddBeer()
    {
        $middleware = new ManipulateInput;
        $request = new Request(
            [
                'name' => 'Damian',
                'beers' => 4,
            ],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => '/json'],
            json_encode(['age' => 28])
        );

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('Damian', $request->input('name'));
            $this->assertEquals(27, $request->input('age'));
            $this->assertEquals(5, $request->input('beers'));
        });
    }
}

class ManipulateInput extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if ($key === 'beers') {
            $value++;
        }
        if ($key === 'age') {
            $value--;
        }

        return $value;
    }
}
