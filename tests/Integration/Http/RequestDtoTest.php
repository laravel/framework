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

    public function testAutoRulesFromTypesWithNoManualRules()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => 30]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormAutoRules::class);

        $this->assertSame('Taylor', $actual->name);
        $this->assertSame(30, $actual->age);
        $this->assertNull($actual->bio);
        $this->assertSame(SortDirection::Asc, $actual->sort);
    }

    public function testAutoRulesRequiredFieldsMustBePresent()
    {
        $request = Request::create('');
        $this->app->instance('request', $request);

        try {
            $this->app->make(MyTypedFormAutoRules::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->errors());
            $this->assertArrayHasKey('age', $e->errors());
            $this->assertArrayNotHasKey('bio', $e->errors());
            $this->assertArrayNotHasKey('sort', $e->errors());
            $this->assertArrayNotHasKey('active', $e->errors());
        }
    }

    public function testAutoRulesRejectsWrongType()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => 'not-a-number']);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedFormAutoRules::class);
    }

    public function testAutoRulesOptionalFieldsCanBeOmitted()
    {
        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => 25]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(MyTypedFormAutoRules::class);

        $this->assertNull($actual->bio);
        $this->assertSame(SortDirection::Asc, $actual->sort);
        $this->assertTrue($actual->active);
    }

    public function testManualRulesMergeWithAutoRules()
    {
        // min:1 comes from manual rules(), required+integer come from auto-rules
        // age=-5 should fail on min:1 (manual)
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => -5]);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedFormAutoRulesWithOverride::class);
    }

    public function testAutoRulesStillApplyWhenManualRulesOmitThem()
    {
        // Manual rules() only has min:1,max:120 for age — no 'required' or 'integer'
        // 'required' should still apply from auto-rules (age has no default)
        $request = Request::create('', parameters: ['name' => 'Taylor']);
        $this->app->instance('request', $request);

        try {
            $this->app->make(MyTypedFormAutoRulesWithOverride::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('age', $e->errors());
        }
    }

    public function testAutoRulesTypeCheckStillAppliesWhenManualRulesOmitIt()
    {
        // Manual rules() only has min:1,max:120 for age — no 'integer'
        // 'integer' should still apply from auto-rules
        $this->expectException(ValidationException::class);

        $request = Request::create('', parameters: ['name' => 'Taylor', 'age' => 'not-a-number']);
        $this->app->instance('request', $request);

        $this->app->make(MyTypedFormAutoRulesWithOverride::class);
    }

    public function testNestedTypedFormRequestValidatesAndBuilds()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CreateOrderRequest::class);

        $this->assertInstanceOf(CreateOrderRequest::class, $actual);
        $this->assertSame('Widget', $actual->item);
        $this->assertInstanceOf(Address::class, $actual->address);
        $this->assertSame('123 Main St', $actual->address->street);
        $this->assertSame('Springfield', $actual->address->city);
        $this->assertNull($actual->address->zip);
    }

    public function testNestedTypedFormRequestFailsValidationOnMissingNestedField()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'address' => [
                'city' => 'Springfield',
                // street is missing
            ],
        ]);
        $this->app->instance('request', $request);

        try {
            $this->app->make(CreateOrderRequest::class);
            self::fail('No exception thrown!');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('address.street', $e->errors());
        }
    }

    public function testNestedTypedFormRequestDefaultsApply()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                // zip omitted, should get default null
            ],
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CreateOrderRequest::class);

        $this->assertNull($actual->address->zip);
    }

    public function testOptionalNestedTypedFormRequestOmittedEntirely()
    {
        $request = Request::create('', parameters: [
            'item' => 'Widget',
            // address is omitted entirely
        ]);
        $this->app->instance('request', $request);

        $actual = $this->app->make(CreateOrderRequestWithOptionalAddress::class);

        $this->assertInstanceOf(CreateOrderRequestWithOptionalAddress::class, $actual);
        $this->assertSame('Widget', $actual->item);
        $this->assertNull($actual->address);
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

// No rules() method — all validation comes from constructor types
class MyTypedFormAutoRules extends TypedFormRequest
{
    public function __construct(
        public string $name,
        public int $age,
        public ?string $bio = null,
        public ?SortDirection $sort = SortDirection::Asc,
        public bool $active = true,
    ) {
    }
}

// Auto-rules as base, manual rules() adds constraints on top
class MyTypedFormAutoRulesWithOverride extends TypedFormRequest
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }

    // Only provides extra constraints — relies on auto-rules for 'required' and 'integer'
    public static function rules(): array
    {
        return [
            'age' => ['min:1', 'max:120'],
        ];
    }
}

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}

class Address extends TypedFormRequest
{
    public function __construct(
        public string $street,
        public string $city,
        public ?string $zip = null,
    ) {
    }
}

class CreateOrderRequest extends TypedFormRequest
{
    public function __construct(
        public string $item,
        public Address $address,
    ) {
    }
}

class CreateOrderRequestWithOptionalAddress extends TypedFormRequest
{
    public function __construct(
        public string $item,
        public ?Address $address = null,
    ) {
    }
}
