<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Contracts\Validation\CastsValidatedValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ValidatedInput;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\InvalidCastException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationCastIntegrationTest extends TestCase
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
    }

    public function test_validator_validate_and_cast()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'age' => '25',
            'active' => '1',
        ], [
            'age' => 'required|integer',
            'active' => 'required|boolean',
        ]);

        $validator->casts([
            'age' => 'int',
            'active' => 'bool',
        ]);

        $result = $validator->validateAndCast();

        $this->assertSame(25, $result['age']);
        $this->assertTrue($result['active']);
    }

    public function test_validator_validate_and_cast_throws_on_validation_failure()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, ['age' => 'not-a-number'], ['age' => 'required|integer']);

        $validator->casts(['age' => 'int']);

        $this->expectException(ValidationException::class);

        $validator->validateAndCast();
    }

    public function test_validator_casted()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'price' => '19.99',
        ], [
            'price' => 'required|numeric',
        ]);

        $validator->casts(['price' => 'float']);

        $this->assertTrue($validator->passes());

        $result = $validator->casted();

        $this->assertSame(19.99, $result['price']);
    }

    public function test_validator_casted_returns_uncasted_when_no_casts_defined()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'price' => '19.99',
        ], [
            'price' => 'required|numeric',
        ]);

        $this->assertTrue($validator->passes());

        $result = $validator->casted();

        $this->assertSame('19.99', $result['price']);
    }

    public function test_validator_safe_casted()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'age' => '25',
            'name' => 'John',
        ], [
            'age' => 'required|integer',
            'name' => 'required|string',
        ]);

        $validator->casts(['age' => 'int']);
        $this->assertTrue($validator->passes());

        $result = $validator->safeCasted();

        $this->assertInstanceOf(ValidatedInput::class, $result);
        $this->assertSame(25, $result['age']);
        $this->assertSame('John', $result['name']);
    }

    public function test_validator_safe_casted_with_keys()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'age' => '25',
            'name' => 'John',
        ], [
            'age' => 'required|integer',
            'name' => 'required|string',
        ]);

        $validator->casts(['age' => 'int']);
        $this->assertTrue($validator->passes());

        $result = $validator->safeCasted(['age']);

        $this->assertSame(25, $result['age']);
        $this->assertArrayNotHasKey('name', $result);
    }

    public function test_validator_casts_with_wildcards()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
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

    public function test_validator_casts_with_dates()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'start_date' => '2026-01-15',
            'end_date' => '2026-12-31 23:59:59',
        ], [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $validator->casts([
            'start_date' => 'date',
            'end_date' => 'datetime',
        ]);

        $result = $validator->validateAndCast();

        $this->assertInstanceOf(Carbon::class, $result['start_date']);
        $this->assertInstanceOf(Carbon::class, $result['end_date']);
        $this->assertSame('00:00:00', $result['start_date']->format('H:i:s'));
    }

    public function test_validator_casts_with_decimals()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'amount' => '19.999',
        ], [
            'amount' => 'required|numeric',
        ]);

        $validator->casts(['amount' => 'decimal:2']);

        $result = $validator->validateAndCast();

        $this->assertSame('20.00', $result['amount']);
    }

    public function test_validator_casts_with_enums()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'status' => 'pending',
        ], [
            'status' => 'required|string',
        ]);

        $validator->casts(['status' => ValidationCastIntegrationTestStatus::class]);

        $result = $validator->validateAndCast();

        $this->assertSame(ValidationCastIntegrationTestStatus::Pending, $result['status']);
    }

    public function test_validator_casts_with_collection()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'tags' => ['foo', 'bar', 'baz'],
        ], [
            'tags' => 'required|array',
        ]);

        $validator->casts(['tags' => 'collection']);

        $result = $validator->validateAndCast();

        $this->assertInstanceOf(Collection::class, $result['tags']);
        $this->assertSame(['foo', 'bar', 'baz'], $result['tags']->all());
    }

    public function test_validator_casts_only_affects_validated_data()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
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

    public function test_validator_casts_with_custom_cast_object()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, [
            'email' => 'test@example.com',
        ], [
            'email' => 'required|email',
        ]);

        $validator->casts(['email' => new ValidationCastIntegrationTestEmailCast]);

        $result = $validator->validateAndCast();

        $this->assertInstanceOf(ValidationCastIntegrationTestEmail::class, $result['email']);
        $this->assertSame('test@example.com', $result['email']->value);
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

    public function test_cast_invalid_type_throws_exception()
    {
        $trans = $this->getTranslator();
        $validator = new Validator($trans, ['val' => 'test'], ['val' => 'required']);

        $validator->casts(['val' => 'encrypted']);

        $this->assertTrue($validator->passes());

        $this->expectException(InvalidCastException::class);
        $this->expectExceptionMessage('not supported for validation');

        $validator->casted();
    }

    protected function getTranslator()
    {
        return new Translator(new ArrayLoader, 'en');
    }
}

enum ValidationCastIntegrationTestStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
}

class ValidationCastIntegrationTestEmail
{
    public function __construct(public string $value)
    {
    }
}

class ValidationCastIntegrationTestEmailCast implements CastsValidatedValue
{
    public function cast(mixed $value, string $key, array $attributes)
    {
        return new ValidationCastIntegrationTestEmail($value);
    }
}
