<?php

namespace Illuminate\Tests\Validation;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\CastsValidatedValue;
use Illuminate\Contracts\Validation\ValidationCastable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\InvalidCastException;
use Illuminate\Validation\ValidationCaster;
use PHPUnit\Framework\TestCase;

class ValidationCasterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Date::use(Carbon::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
        Date::use(Carbon::class);
    }

    public function test_cast_integer()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['age' => '25'], ['age' => 'int']);
        $this->assertSame(25, $result['age']);

        $result = $caster->apply(['age' => '25'], ['age' => 'integer']);
        $this->assertSame(25, $result['age']);
    }

    public function test_cast_boolean()
    {
        $caster = new ValidationCaster;

        $this->assertTrue($caster->apply(['active' => '1'], ['active' => 'bool'])['active']);
        $this->assertTrue($caster->apply(['active' => 'true'], ['active' => 'bool'])['active']);
        $this->assertTrue($caster->apply(['active' => 'on'], ['active' => 'bool'])['active']);
        $this->assertTrue($caster->apply(['active' => 'yes'], ['active' => 'bool'])['active']);
        $this->assertTrue($caster->apply(['active' => 1], ['active' => 'bool'])['active']);
        $this->assertTrue($caster->apply(['active' => true], ['active' => 'bool'])['active']);

        $this->assertFalse($caster->apply(['active' => '0'], ['active' => 'bool'])['active']);
        $this->assertFalse($caster->apply(['active' => 'false'], ['active' => 'bool'])['active']);
        $this->assertFalse($caster->apply(['active' => 'no'], ['active' => 'bool'])['active']);
        $this->assertFalse($caster->apply(['active' => 0], ['active' => 'bool'])['active']);
        $this->assertFalse($caster->apply(['active' => false], ['active' => 'bool'])['active']);
    }

    public function test_cast_float()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['price' => '19.99'], ['price' => 'float']);

        $this->assertSame(19.99, $result['price']);
    }

    public function test_cast_float_aliases()
    {
        $caster = new ValidationCaster;

        $this->assertSame(19.99, $caster->apply(['val' => '19.99'], ['val' => 'double'])['val']);
        $this->assertSame(19.99, $caster->apply(['val' => '19.99'], ['val' => 'real'])['val']);
    }

    public function test_cast_string()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['value' => 123], ['value' => 'string']);

        $this->assertSame('123', $result['value']);
    }

    public function test_cast_array()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['data' => '{"foo":"bar"}'], ['data' => 'array']);
        $this->assertSame(['foo' => 'bar'], $result['data']);

        $result = $caster->apply(['data' => ['foo' => 'bar']], ['data' => 'array']);
        $this->assertSame(['foo' => 'bar'], $result['data']);

        $result = $caster->apply(['data' => 'hello'], ['data' => 'array']);
        $this->assertSame([], $result['data']);
    }

    public function test_cast_collection()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['items' => [1, 2, 3]], ['items' => 'collection']);

        $this->assertInstanceOf(Collection::class, $result['items']);
        $this->assertSame([1, 2, 3], $result['items']->all());
    }

    public function test_cast_date()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['date' => '2026-01-15'], ['date' => 'date']);

        $this->assertInstanceOf(Carbon::class, $result['date']);
        $this->assertSame('2026-01-15 00:00:00', $result['date']->format('Y-m-d H:i:s'));
    }

    public function test_cast_date_with_format()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['date' => '15/01/2026'], ['date' => 'date:d/m/Y']);

        $this->assertInstanceOf(Carbon::class, $result['date']);
        $this->assertSame('2026-01-15', $result['date']->format('Y-m-d'));
    }

    public function test_cast_date_time()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['datetime' => '2026-01-15 14:30:00'], ['datetime' => 'datetime']);

        $this->assertInstanceOf(Carbon::class, $result['datetime']);
        $this->assertSame('2026-01-15 14:30:00', $result['datetime']->format('Y-m-d H:i:s'));
    }

    public function test_cast_date_time_with_format()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['datetime' => '15/01/2026 14:30'], ['datetime' => 'datetime:d/m/Y H:i']);

        $this->assertInstanceOf(Carbon::class, $result['datetime']);
        $this->assertSame('2026-01-15 14:30', $result['datetime']->format('Y-m-d H:i'));
    }

    public function test_cast_immutable_date()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['date' => '2026-01-15'], ['date' => 'immutable_date']);

        $this->assertInstanceOf(CarbonImmutable::class, $result['date']);
        $this->assertSame('2026-01-15 00:00:00', $result['date']->format('Y-m-d H:i:s'));
    }

    public function test_cast_immutable_date_time()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['datetime' => '2026-01-15 14:30:00'], ['datetime' => 'immutable_datetime']);

        $this->assertInstanceOf(CarbonImmutable::class, $result['datetime']);
        $this->assertSame('2026-01-15 14:30:00', $result['datetime']->format('Y-m-d H:i:s'));
    }

    public function test_cast_decimal_defaults_to_string()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['price' => '19.999'], ['price' => 'decimal:2']);

        $this->assertSame('20.00', $result['price']);
        $this->assertIsString($result['price']);
    }

    public function test_cast_decimal_with_different_precision()
    {
        $caster = new ValidationCaster;

        $this->assertSame('19.9990', $caster->apply(['val' => '19.999'], ['val' => 'decimal:4'])['val']);
        $this->assertSame('20', $caster->apply(['val' => '19.999'], ['val' => 'decimal:0'])['val']);
    }

    public function test_cast_null_preserves_null()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['value' => null], ['value' => 'int']);

        $this->assertNull($result['value']);
    }

    public function test_cast_empty_string_preserved()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['value' => ''], ['value' => 'string']);

        $this->assertSame('', $result['value']);
    }

    public function test_cast_backed_enum()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(['status' => 'pending'], ['status' => ValidationCastTestStatus::class]);

        $this->assertSame(ValidationCastTestStatus::Pending, $result['status']);
    }

    public function test_cast_backed_enum_from_throws_on_invalid()
    {
        $caster = new ValidationCaster;

        $this->expectException(\ValueError::class);

        $caster->apply(['status' => 'invalid'], ['status' => ValidationCastTestStatus::class]);
    }

    public function test_cast_wildcard_paths()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply([
            'items' => [
                ['qty' => '10', 'price' => '19.99'],
                ['qty' => '5', 'price' => '29.99'],
            ],
        ], [
            'items.*.qty' => 'int',
            'items.*.price' => 'float',
        ]);

        $this->assertSame(10, $result['items'][0]['qty']);
        $this->assertSame(19.99, $result['items'][0]['price']);
        $this->assertSame(5, $result['items'][1]['qty']);
        $this->assertSame(29.99, $result['items'][1]['price']);
    }

    public function test_cast_nested_wildcard_paths()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply([
            'orders' => [
                [
                    'items' => [
                        ['qty' => '1'],
                        ['qty' => '2'],
                    ],
                ],
                [
                    'items' => [
                        ['qty' => '3'],
                    ],
                ],
            ],
        ], [
            'orders.*.items.*.qty' => 'int',
        ]);

        $this->assertSame(1, $result['orders'][0]['items'][0]['qty']);
        $this->assertSame(2, $result['orders'][0]['items'][1]['qty']);
        $this->assertSame(3, $result['orders'][1]['items'][0]['qty']);
    }

    public function test_cast_specificity_exact_path_over_wildcard()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply([
            'items' => [
                ['qty' => '10'],
                ['qty' => '20'],
            ],
        ], [
            'items.*.qty' => 'int',
            'items.0.qty' => 'string', // More specific - should win
        ]);

        $this->assertSame('10', $result['items'][0]['qty']); // String due to specific path
        $this->assertSame(20, $result['items'][1]['qty']); // Int due to wildcard
    }

    public function test_cast_custom_cast_object()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(
            ['email' => 'test@example.com'],
            ['email' => new ValidationCastTestEmailCast]
        );

        $this->assertInstanceOf(ValidationCastTestEmail::class, $result['email']);
        $this->assertSame('test@example.com', $result['email']->value);
    }

    public function test_cast_castable_for_validation()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply(
            ['money' => '100.00'],
            ['money' => ValidationCastTestMoney::class]
        );

        $this->assertInstanceOf(ValidationCastTestMoney::class, $result['money']);
        $this->assertSame('100.00', $result['money']->amount);
    }

    public function test_cast_nested_object_to_dto()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply([
            'product_id' => '123',
            'shipping' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip' => '12345',
            ],
        ], [
            'product_id' => 'int',
            'shipping' => ShippingAddressData::class,
        ]);

        $this->assertSame(123, $result['product_id']);
        $this->assertInstanceOf(ShippingAddressData::class, $result['shipping']);
        $this->assertSame('123 Main St', $result['shipping']->street);
        $this->assertSame('Springfield', $result['shipping']->city);
        $this->assertSame('12345', $result['shipping']->zip);
    }

    public function test_cast_flat_data_to_dto_with_explicit_selection()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'age' => '28',
            'newsletter' => 'yes',
        ], [
            'age' => 'int',
            'newsletter' => 'bool',
            'name' => CreateUserData::class,
        ]);

        $this->assertInstanceOf(CreateUserData::class, $result['name']);
        $this->assertSame('Jane Doe', $result['name']->name);
        $this->assertSame('jane@example.com', $result['name']->email);
        $this->assertSame(28, $result['name']->age);
    }

    public function test_cast_unsupported_type_throws_exception()
    {
        $caster = new ValidationCaster;

        $this->expectException(InvalidCastException::class);
        $this->expectExceptionMessage('not supported for validation');

        $caster->apply(['value' => 'test'], ['value' => 'encrypted']);
    }

    public function test_cast_invalid_spec_throws_exception()
    {
        $caster = new ValidationCaster;

        $this->expectException(InvalidCastException::class);
        $this->expectExceptionMessage('Invalid cast specification');

        $caster->apply(['value' => 'test'], ['value' => 'invalid_type_that_does_not_exist']);
    }

    public function test_cast_class_without_interface_throws_exception()
    {
        $caster = new ValidationCaster;

        $this->expectException(InvalidCastException::class);
        $this->expectExceptionMessage('must implement CastsValidatedValue, ValidationCastable, or be an enum');

        $caster->apply(['value' => 'test'], ['value' => ValidationCastTestPlainClass::class]);
    }

    public function test_only_defined_paths_are_cast()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply([
            'age' => '25',
            'name' => 'John',
        ], [
            'age' => 'int',
        ]);

        $this->assertSame(25, $result['age']);
        $this->assertSame('John', $result['name']);
    }

    public function test_missing_paths_are_ignored()
    {
        $caster = new ValidationCaster;

        $result = $caster->apply([
            'name' => 'John',
        ], [
            'age' => 'int',
            'name' => 'string',
        ]);

        $this->assertArrayNotHasKey('age', $result);
        $this->assertSame('John', $result['name']);
    }
}

enum ValidationCastTestStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
}

class ValidationCastTestEmail
{
    public function __construct(public string $value)
    {
    }
}

class ValidationCastTestEmailCast implements CastsValidatedValue
{
    public function cast(mixed $value, string $key, array $attributes)
    {
        return new ValidationCastTestEmail($value);
    }
}

class ValidationCastTestMoney implements ValidationCastable
{
    public function __construct(public string $amount)
    {
    }

    public static function castUsing(array $arguments)
    {
        return new class implements CastsValidatedValue
        {
            public function cast(mixed $value, string $key, array $attributes)
            {
                return new ValidationCastTestMoney($value);
            }
        };
    }
}

class ValidationCastTestPlainClass
{
    public function __construct(public string $value)
    {
    }
}

class ShippingAddressData implements ValidationCastable
{
    public function __construct(
        public string $street,
        public string $city,
        public string $zip,
    ) {
    }

    public static function castUsing(array $arguments)
    {
        return new class implements CastsValidatedValue
        {
            public function cast(mixed $value, string $key, array $attributes)
            {
                return new ShippingAddressData(...$value);
            }
        };
    }
}

class CreateUserData implements ValidationCastable
{
    public function __construct(
        public string $name,
        public string $email,
        public int $age,
    ) {
    }

    public static function castUsing(array $arguments)
    {
        return new class implements CastsValidatedValue
        {
            public function cast(mixed $value, string $key, array $attributes)
            {
                return new CreateUserData(
                    name: $attributes['name'],
                    email: $attributes['email'],
                    age: $attributes['age'],
                );
            }
        };
    }
}
