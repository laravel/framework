<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\Attributes\Rules;
use Illuminate\Foundation\Http\RequestDto;
use Illuminate\Foundation\Http\SimplifiedRequestDto;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

    public function testSimplifiedRequestDtoValidatesAndBuilds()
    {
        $request = Request::create('', parameters: ['number' => 42, 'string' => 'a']);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MySimplifiedDto::class);

        $this->assertInstanceOf(MySimplifiedDto::class, $actual);
        $this->assertEquals(42, $actual->number);
        $this->assertEquals('a', $actual->string);
    }

    public function testSimplifiedRequestDtoFailsValidation()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['number' => 999, 'string' => 'z']);
        $this->app->instance('request', $request);

        $this->app->make(MySimplifiedDto::class);
    }

    public function testSimplifiedRequestDtoFailsAuthorization()
    {
        $this->expectException(AuthorizationException::class);

        $request = Request::create('', parameters: ['number' => 42, 'string' => 'a']);
        $this->app->instance('request', $request);

        $this->app->make(MyUnauthorizedSimplifiedDto::class);
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

class MySimplifiedDto extends SimplifiedRequestDto
{
    public function __construct(
        public int $number,
        public string $string,
    ) {
    }

    public static function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1', 'max:100'],
            'string' => ['required', 'string', 'in:a,b,c'],
        ];
    }
}

class MyUnauthorizedSimplifiedDto extends SimplifiedRequestDto
{
    public function __construct(
        public int $number,
        public string $string,
    ) {
    }

    public static function rules(): array
    {
        return [
            'number' => ['required', 'integer'],
            'string' => ['required', 'string'],
        ];
    }

    public static function authorize(): bool
    {
        return false;
    }
}
