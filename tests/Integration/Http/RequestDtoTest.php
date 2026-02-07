<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Foundation\Http\Attributes\Rules;
use Illuminate\Foundation\Http\RequestDto;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;

class RequestDtoTest extends TestCase
{
    public function testResolve()
    {
        $this->app->make(MyRequestDto::class);
    }
    public function testSimpleRequestDto()
    {
        $request = Request::create('', parameters: ['hello' => 'world']);
        $this->app->instance('request', $request);
        dd(app('request'));
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
}

class RequestBuilder
{
    /**
     * @param  Request  $request
     * @param  class-string  $class
     * @param  \Closure  $rulesBuilder
     * @param  \Closure  $auth
     * @return void
     */
    public function build(
        Request $request,
        string $class,
        \Closure $rulesBuilder,
        \Closure $auth
    ) {

        $toValidate = $request->all();

    }
}
