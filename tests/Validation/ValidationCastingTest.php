<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\CastsValue;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactoryContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ValidatedInput;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator as ConcreteTranslator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ValidationCastingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Date::use(Carbon::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
        Date::use(Carbon::class);
        m::close();
    }

    public function test_validator_get_casts()
    {
        $validator = new Validator($this->getTranslator(), [], []);
        $validator->casts(['age' => 'int', 'active' => 'bool']);

        $this->assertSame(['age' => 'int', 'active' => 'bool'], $validator->getCasts());
    }

    public function test_validator_get_casts_returns_null_when_not_set()
    {
        $validator = new Validator($this->getTranslator(), [], []);

        $this->assertNull($validator->getCasts());
    }

    public function test_validator_validate_and_cast()
    {
        $validator = new Validator($this->getTranslator(), [
            'age' => '25',
            'active' => '1',
        ], [
            'age' => 'required|integer',
            'active' => 'required|boolean',
        ]);

        $validator->casts(['age' => 'int', 'active' => 'bool']);

        $result = $validator->validateAndCast();

        $this->assertSame(25, $result['age']);
        $this->assertTrue($result['active']);
    }

    public function test_validator_validate_and_cast_throws_on_validation_failure()
    {
        $validator = new Validator($this->getTranslator(), [
            'age' => 'not-a-number',
        ], [
            'age' => 'required|integer',
        ]);

        $validator->casts(['age' => 'int']);

        $this->expectException(ValidationException::class);

        $validator->validateAndCast();
    }

    public function test_validator_validate_and_cast_returns_uncasted_when_no_casts()
    {
        $validator = new Validator($this->getTranslator(), [
            'age' => '25',
        ], [
            'age' => 'required|integer',
        ]);

        $result = $validator->validateAndCast();

        $this->assertSame('25', $result['age']);
    }

    public function test_validator_casted_returns_all_casted_data()
    {
        $validator = new Validator($this->getTranslator(), [
            'price' => '19.99',
        ], [
            'price' => 'required|numeric',
        ]);

        $validator->casts(['price' => 'float']);
        $validator->passes();

        $result = $validator->casted();

        $this->assertSame(19.99, $result['price']);
    }

    public function test_validator_casted_with_key()
    {
        $validator = new Validator($this->getTranslator(), [
            'price' => '19.99',
            'qty' => '5',
        ], [
            'price' => 'required|numeric',
            'qty' => 'required|integer',
        ]);

        $validator->casts(['price' => 'float', 'qty' => 'int']);
        $validator->passes();

        $this->assertSame(19.99, $validator->casted('price'));
        $this->assertSame(5, $validator->casted('qty'));
    }

    public function test_validator_casted_with_default()
    {
        $validator = new Validator($this->getTranslator(), [
            'price' => '19.99',
        ], [
            'price' => 'required|numeric',
        ]);

        $validator->casts(['price' => 'float']);
        $validator->passes();

        $this->assertSame('default', $validator->casted('missing', 'default'));
    }

    public function test_validator_casted_returns_uncasted_when_no_casts_defined()
    {
        $validator = new Validator($this->getTranslator(), [
            'price' => '19.99',
        ], [
            'price' => 'required|numeric',
        ]);

        $validator->passes();

        $result = $validator->casted();

        $this->assertSame('19.99', $result['price']);
    }

    public function test_validator_safe_casted()
    {
        $validator = new Validator($this->getTranslator(), [
            'age' => '25',
            'name' => 'John',
        ], [
            'age' => 'required|integer',
            'name' => 'required|string',
        ]);

        $validator->casts(['age' => 'int']);
        $validator->passes();

        $result = $validator->safeCasted();

        $this->assertInstanceOf(ValidatedInput::class, $result);
        $this->assertSame(25, $result['age']);
        $this->assertSame('John', $result['name']);
    }

    public function test_validator_safe_casted_with_keys()
    {
        $validator = new Validator($this->getTranslator(), [
            'age' => '25',
            'name' => 'John',
        ], [
            'age' => 'required|integer',
            'name' => 'required|string',
        ]);

        $validator->casts(['age' => 'int']);
        $validator->passes();

        $result = $validator->safeCasted(['age']);

        $this->assertSame(25, $result['age']);
        $this->assertArrayNotHasKey('name', $result);
    }

    public function test_validator_casts_only_affects_validated_data()
    {
        $validator = new Validator($this->getTranslator(), [
            'age' => '25',
            'extra' => 'not-validated',
        ], [
            'age' => 'required|integer',
        ]);

        $validator->casts(['age' => 'int', 'extra' => 'int']);

        $result = $validator->validateAndCast();

        $this->assertSame(25, $result['age']);
        $this->assertArrayNotHasKey('extra', $result);
    }

    public function test_validator_casts_with_wildcards()
    {
        $validator = new Validator($this->getTranslator(), [
            'items' => [
                ['qty' => '10', 'price' => '19.99'],
                ['qty' => '5', 'price' => '29.99'],
            ],
        ], [
            'items' => 'required|array',
            'items.*.qty' => 'required|integer',
            'items.*.price' => 'required|numeric',
        ]);

        $validator->casts([
            'items.*.qty' => 'int',
            'items.*.price' => 'float',
        ]);

        $result = $validator->validateAndCast();

        $this->assertSame(10, $result['items'][0]['qty']);
        $this->assertSame(19.99, $result['items'][0]['price']);
        $this->assertSame(5, $result['items'][1]['qty']);
        $this->assertSame(29.99, $result['items'][1]['price']);
    }

    public function test_factory_validate_and_cast()
    {
        $factory = new Factory($this->getTranslator());

        $result = $factory->validateAndCast(
            ['age' => '25', 'active' => '1'],
            ['age' => 'required|integer', 'active' => 'required|boolean'],
            ['age' => 'int', 'active' => 'bool']
        );

        $this->assertSame(25, $result['age']);
        $this->assertTrue($result['active']);
    }

    public function test_factory_validate_and_cast_throws_on_failure()
    {
        $factory = new Factory($this->getTranslator());

        $this->expectException(ValidationException::class);

        $factory->validateAndCast(
            ['age' => 'not-a-number'],
            ['age' => 'required|integer'],
            ['age' => 'int']
        );
    }

    public function test_form_request_casted()
    {
        $request = $this->createFormRequest(
            ['age' => '25', 'name' => 'John'],
            ValidationCastingTestFormRequestWithCasts::class
        );

        $request->validateResolved();

        $result = $request->casted();

        $this->assertSame(25, $result['age']);
        $this->assertSame('John', $result['name']);
    }

    public function test_form_request_casted_with_key()
    {
        $request = $this->createFormRequest(
            ['age' => '25', 'name' => 'John'],
            ValidationCastingTestFormRequestWithCasts::class
        );

        $request->validateResolved();

        $this->assertSame(25, $request->casted('age'));
        $this->assertSame('John', $request->casted('name'));
    }

    public function test_form_request_casted_with_default()
    {
        $request = $this->createFormRequest(
            ['age' => '25', 'name' => 'John'],
            ValidationCastingTestFormRequestWithCasts::class
        );

        $request->validateResolved();

        $this->assertSame('default', $request->casted('missing', 'default'));
    }

    public function test_form_request_validated_with_casts()
    {
        $request = $this->createFormRequest(
            ['age' => '25', 'name' => 'John'],
            ValidationCastingTestFormRequestWithCasts::class
        );

        $request->validateResolved();

        $result = $request->validatedWithCasts();

        $this->assertSame(25, $result['age']);
        $this->assertSame('John', $result['name']);
    }

    public function test_form_request_safe_cast()
    {
        $request = $this->createFormRequest(
            ['age' => '25', 'name' => 'John'],
            ValidationCastingTestFormRequestWithCasts::class
        );

        $request->validateResolved();

        $result = $request->safeCast();

        $this->assertInstanceOf(ValidatedInput::class, $result);
        $this->assertSame(25, $result['age']);
    }

    public function test_form_request_safe_cast_with_keys()
    {
        $request = $this->createFormRequest(
            ['age' => '25', 'name' => 'John'],
            ValidationCastingTestFormRequestWithCasts::class
        );

        $request->validateResolved();

        $result = $request->safeCast(['age']);

        $this->assertSame(25, $result['age']);
        $this->assertArrayNotHasKey('name', $result);
    }

    public function test_form_request_casted_without_casts_returns_validated()
    {
        $request = $this->createFormRequest(
            ['name' => 'John'],
            ValidationCastingTestFormRequestWithoutCasts::class
        );

        $request->validateResolved();

        $result = $request->casted();

        $this->assertSame('John', $result['name']);
    }

    public function test_form_request_casted_uses_data_after_prepare_for_validation()
    {
        $request = $this->createFormRequest(
            ['age' => '25'],
            ValidationCastingTestFormRequestWithPrepare::class
        );

        $request->validateResolved();

        $result = $request->casted();

        $this->assertSame(100, $result['age']);
    }

    public function test_form_request_casted_reflects_passed_validation_changes()
    {
        $request = $this->createFormRequest(
            ['name' => 'John'],
            ValidationCastingTestFormRequestWithPassedValidation::class
        );

        $request->validateResolved();
        $result = $request->casted();

        // validated() returns what was validated, not what passedValidation changed
        $this->assertSame('John', $result['name']);
    }

    public function test_form_request_with_custom_cast_object()
    {
        $request = $this->createFormRequest(
            ['email' => 'test@example.com'],
            ValidationCastingTestFormRequestWithCustomCast::class
        );

        $request->validateResolved();

        $result = $request->casted();

        $this->assertInstanceOf(ValidationCastingTestEmail::class, $result['email']);
        $this->assertSame('test@example.com', $result['email']->value);
    }

    public function test_form_request_with_wildcards()
    {
        $request = $this->createFormRequest(
            ['items' => [['qty' => '10'], ['qty' => '20']]],
            ValidationCastingTestFormRequestWithWildcards::class
        );

        $request->validateResolved();

        $result = $request->casted();

        $this->assertSame(10, $result['items'][0]['qty']);
        $this->assertSame(20, $result['items'][1]['qty']);
    }

    protected function getTranslator()
    {
        return new ConcreteTranslator(new ArrayLoader, 'en');
    }

    protected function createFormRequest(array $payload, string $class): FormRequest
    {
        $container = tap(new Container, function ($container) {
            $container->instance(
                ValidationFactoryContract::class,
                $this->createValidationFactory($container)
            );
        });

        $request = $class::create('/', 'GET', $payload);

        return $request->setRedirector($this->createMockRedirector())
            ->setContainer($container);
    }

    protected function createValidationFactory($container)
    {
        $translator = m::mock(Translator::class)->shouldReceive('get')
            ->zeroOrMoreTimes()->andReturn('error')->getMock();

        return new Factory($translator, $container);
    }

    protected function createMockRedirector()
    {
        $redirector = m::mock(Redirector::class);
        $redirector->shouldReceive('getUrlGenerator')->zeroOrMoreTimes()
            ->andReturn(m::mock(UrlGenerator::class));

        return $redirector;
    }
}

class ValidationCastingTestFormRequestWithCasts extends FormRequest
{
    public function rules()
    {
        return ['age' => 'required|integer', 'name' => 'required|string'];
    }

    public function casts()
    {
        return ['age' => 'int'];
    }

    public function authorize()
    {
        return true;
    }
}

class ValidationCastingTestFormRequestWithoutCasts extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required|string'];
    }

    public function authorize()
    {
        return true;
    }
}

class ValidationCastingTestFormRequestWithPrepare extends FormRequest
{
    public function rules()
    {
        return ['age' => 'required|integer'];
    }

    public function casts()
    {
        return ['age' => 'int'];
    }

    public function prepareForValidation()
    {
        $this->merge(['age' => '100']);
    }

    public function authorize()
    {
        return true;
    }
}

class ValidationCastingTestFormRequestWithPassedValidation extends FormRequest
{
    public function rules()
    {
        return ['name' => 'required|string'];
    }

    public function casts()
    {
        return ['name' => 'string'];
    }

    public function passedValidation()
    {
        $this->replace(['name' => 'Modified']);
    }

    public function authorize()
    {
        return true;
    }
}

class ValidationCastingTestFormRequestWithCustomCast extends FormRequest
{
    public function rules()
    {
        return ['email' => 'required|email'];
    }

    public function casts()
    {
        return ['email' => new ValidationCastingTestEmailCast];
    }

    public function authorize()
    {
        return true;
    }
}

class ValidationCastingTestFormRequestWithWildcards extends FormRequest
{
    public function rules()
    {
        return [
            'items' => 'required|array',
            'items.*.qty' => 'required|integer',
        ];
    }

    public function casts()
    {
        return ['items.*.qty' => 'int'];
    }

    public function authorize()
    {
        return true;
    }
}

class ValidationCastingTestEmail
{
    public function __construct(public string $value)
    {
    }
}

class ValidationCastingTestEmailCast implements CastsValue
{
    public function cast(mixed $value, string $key, array $attributes)
    {
        return new ValidationCastingTestEmail($value);
    }
}
