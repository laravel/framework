<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Foundation\Http\Attributes\Rules;
use Illuminate\Foundation\Http\RequestDto;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;

class RequestDtoTest extends TestCase
{
    public function testSimpleRequestDto()
    {
        $request = Request::create('', parameters: ['number' => 11, 'string' => 'abc']);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyRequestDto::class);

        $this->assertInstanceOf(MyRequestDto::class, $actual);
        $this->assertEquals(11, $actual->number);
        $this->assertEquals('abc', $actual->string);
    }
}

#[Rules([
    'number' => ['required', 'integer', 'min:1', 'max:100'],
    'string' => ['required', 'string', 'in:a,b,c'],
])]
class MyRequestDto extends RequestDto
{
    public function __construct(
        public int $number,
        public string $string,
    ) {
    }

    protected static function rules()
    {
        return [
            'number' => ['required', 'integer', 'min:1', 'max:100'],
            'string' => ['required'],
        ];
    }
}
