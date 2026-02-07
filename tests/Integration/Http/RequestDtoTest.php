<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\Attributes\Rules;
use Illuminate\Foundation\Http\RequestDto;
use Illuminate\Foundation\Http\TypedFormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $actual = $this->app->make(MyTypedForm::class);

        $this->assertInstanceOf(MyTypedForm::class, $actual);
        $this->assertEquals(42, $actual->number);
        $this->assertEquals('a', $actual->string);
    }

    public function testSimplifiedRequestDtoFailsValidation()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['number' => 999, 'string' => 'z']);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedForm::class);
    }

    public function testSimplifiedRequestDtoFailsAuthorization()
    {
        $this->expectException(AuthorizationException::class);

        $request = Request::create('', parameters: ['number' => 42, 'string' => 'a']);
        $this->app->instance('request', $request);

        $this->app->make(MyUnauthorizedTypedForm::class);
    }

    public function testDefaultPropertyUsedWhenKeyMissingFromRequest()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor']);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertInstanceOf(MyTypedFormWithDefaults::class, $actual);
        $this->assertEquals('Taylor', $actual->name);
        $this->assertEquals(25, $actual->perPage);
    }

    public function testDefaultPropertyNotUsedWhenKeyPresentInRequest()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'perPage' => 50]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertEquals(50, $actual->perPage);
    }

    public function testDefaultPropertyNotUsedWhenKeyPresentWithNullValue()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'perPage' => null]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertNull($actual->perPage);
    }

    public function testDefaultEnumPropertyUsedWhenKeyMissingFromRequest()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor']);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertSame(SortDirection::Desc, $actual->sort);
    }

    public function testEnumPropertyOverriddenWhenKeyPresentInRequest()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'sort' => 'asc']);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormWithDefaults::class);

        $this->assertSame(SortDirection::Asc, $actual->sort);
    }

    public function testEnumPropertyFailsValidationWithInvalidValue()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['name' => 'Taylor', 'sort' => 'invalid']);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedFormWithDefaults::class);
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

class MyTypedForm extends TypedFormRequest
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

class MyUnauthorizedTypedForm extends TypedFormRequest
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

class MyTypedFormWithDefaults extends TypedFormRequest
{
    public function __construct(
        public string $name,
        public ?int $perPage = 25,
        public ?SortDirection $sort = SortDirection::Desc,
    ) {
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'perPage' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', Rule::enum(SortDirection::class)],
        ];
    }
}

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
