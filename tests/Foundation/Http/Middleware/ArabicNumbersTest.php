<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Http\Middleware\ArabicNumbers;

class ArabicNumbersTest extends TestCase
{
    public function testArabicNumbers(): void
    {
        $middleware = new ArabicNumbers;
        $request = new Request(
            [
                'name' => 'Mostafa',
                'age' => '٢٧',
                'cell_number' => '١٢٣٤٥٦',
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('Mostafa', $request->get('name'));
            $this->assertEquals(27, $request->get('age'));
            $this->assertEquals('123456', $request->get('cell_number'));
        });
    }

    public function testAjaxArabicNumbers(): void
    {
        $middleware = new ArabicNumbers;
        $request = new Request([
                'name' => 'Mostafa',
                'age' => '٢٧',
                'cell_number' => '١٢٣٤٥٦',
                'CONTENT_TYPE' => '/json',
            ]
        );

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('Mostafa', $request->get('name'));
            $this->assertEquals('27', $request->get('age'));
            $this->assertEquals('123456', $request->get('cell_number'));
        });
    }
}
