<?php

namespace Illuminate\Tests\Support;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Support\CastsValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Caster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\InvalidCastException;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class CasterTest extends TestCase
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
        Caster::useDateFormat(null);
        Facade::clearResolvedInstances();
        m::close();
    }

    #[DataProvider('primitiveCastProvider')]
    public function test_primitive_casts($cast, $input, $expected)
    {
        $result = Caster::value($input, $cast);

        $this->assertSame($expected, $result);
    }

    public static function primitiveCastProvider(): array
    {
        return [
            'int from string' => ['int', '25', 25],
            'integer from string' => ['integer', '42', 42],
            'int from float string' => ['int', '25.9', 25],
            'bool true from 1' => ['bool', '1', true],
            'bool true from true' => ['bool', 'true', true],
            'bool true from on' => ['bool', 'on', true],
            'bool true from yes' => ['bool', 'yes', true],
            'bool true from YES' => ['bool', 'YES', true],
            'boolean true from int' => ['boolean', 1, true],
            'bool false from 0' => ['bool', '0', false],
            'bool false from false' => ['bool', 'false', false],
            'bool false from no' => ['bool', 'no', false],
            'bool false from empty' => ['bool', '', false],
            'float from string' => ['float', '19.99', 19.99],
            'double from string' => ['double', '19.99', 19.99],
            'real from string' => ['real', '19.99', 19.99],
            'float infinity' => ['float', 'Infinity', INF],
            'float negative infinity' => ['float', '-Infinity', -INF],
            'string from int' => ['string', 123, '123'],
            'string from float' => ['string', 19.99, '19.99'],
        ];
    }

    public function test_cast_float_nan()
    {
        $result = Caster::value('NaN', 'float');

        $this->assertNan($result);
    }

    public function test_cast_array_from_json()
    {
        $result = Caster::value('{"foo":"bar"}', 'array');

        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function test_json_cast_is_alias_for_array()
    {
        $result = Caster::value('{"foo":"bar"}', 'json');

        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function test_cast_array_passthrough()
    {
        $result = Caster::value(['foo' => 'bar'], 'array');

        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function test_cast_array_from_invalid_json_returns_empty()
    {
        $result = Caster::value('not-json', 'array');

        $this->assertSame([], $result);
    }

    public function test_cast_object_from_json()
    {
        $result = Caster::value('{"foo":"bar"}', 'object');

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertSame('bar', $result->foo);
    }

    public function test_cast_object_passthrough()
    {
        $obj = new stdClass;
        $obj->test = 'value';

        $result = Caster::value($obj, 'object');

        $this->assertSame($obj, $result);
    }

    public function test_cast_collection()
    {
        $result = Caster::value([1, 2, 3], 'collection');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame([1, 2, 3], $result->all());
    }

    public function test_cast_date()
    {
        $result = Caster::value('2026-01-15', 'date');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame('2026-01-15 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_cast_date_with_custom_format()
    {
        $result = Caster::value('15/01/2026', 'date:d/m/Y');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame('2026-01-15', $result->format('Y-m-d'));
    }

    public function test_cast_datetime()
    {
        $result = Caster::value('2026-01-15 14:30:00', 'datetime');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame('2026-01-15 14:30:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_cast_datetime_with_custom_format()
    {
        $result = Caster::value('15/01/2026 14:30', 'datetime:d/m/Y H:i');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame('2026-01-15 14:30', $result->format('Y-m-d H:i'));
    }

    public function test_cast_immutable_date()
    {
        $result = Caster::value('2026-01-15', 'immutable_date');

        $this->assertInstanceOf(CarbonImmutable::class, $result);
        $this->assertSame('2026-01-15 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_cast_immutable_datetime()
    {
        $result = Caster::value('2026-01-15 14:30:00', 'immutable_datetime');

        $this->assertInstanceOf(CarbonImmutable::class, $result);
    }

    public function test_cast_timestamp()
    {
        $result = Caster::value('2026-01-15 14:30:00', 'timestamp');

        $this->assertIsInt($result);
        $this->assertSame(Carbon::parse('2026-01-15 14:30:00')->getTimestamp(), $result);
    }

    #[DataProvider('decimalProvider')]
    public function test_cast_decimal($precision, $input, $expected)
    {
        $result = Caster::value($input, "decimal:{$precision}");

        $this->assertSame($expected, $result);
        $this->assertIsString($result);
    }

    public static function decimalProvider(): array
    {
        return [
            'round up' => [2, '19.999', '20.00'],
            'round down' => [2, '19.991', '19.99'],
            '4 decimals' => [4, '19.999', '19.9990'],
            '0 decimals' => [0, '19.999', '20'],
        ];
    }

    public function test_cast_null_preserves_null()
    {
        $caster = Caster::make(['value' => 'int']);
        $result = $caster->cast(['value' => null]);

        $this->assertNull($result['value']);
    }

    public function test_cast_backed_enum()
    {
        $result = Caster::value('pending', CasterTestStatus::class);

        $this->assertSame(CasterTestStatus::Pending, $result);
    }

    public function test_cast_enum_passthrough()
    {
        $result = Caster::value(CasterTestStatus::Pending, CasterTestStatus::class);

        $this->assertSame(CasterTestStatus::Pending, $result);
    }

    public function test_cast_backed_enum_throws_on_invalid()
    {
        $this->expectException(\ValueError::class);

        Caster::value('invalid', CasterTestStatus::class);
    }

    public function test_cast_wildcard_paths()
    {
        $caster = Caster::make([
            'items.*.qty' => 'int',
            'items.*.price' => 'float',
        ]);

        $result = $caster->cast([
            'items' => [
                ['qty' => '10', 'price' => '19.99'],
                ['qty' => '5', 'price' => '29.99'],
            ],
        ]);

        $this->assertSame(10, $result['items'][0]['qty']);
        $this->assertSame(19.99, $result['items'][0]['price']);
        $this->assertSame(5, $result['items'][1]['qty']);
        $this->assertSame(29.99, $result['items'][1]['price']);
    }

    public function test_cast_nested_wildcard_paths()
    {
        $caster = Caster::make(['orders.*.items.*.qty' => 'int']);

        $result = $caster->cast([
            'orders' => [
                ['items' => [['qty' => '1'], ['qty' => '2']]],
                ['items' => [['qty' => '3']]],
            ],
        ]);

        $this->assertSame(1, $result['orders'][0]['items'][0]['qty']);
        $this->assertSame(2, $result['orders'][0]['items'][1]['qty']);
        $this->assertSame(3, $result['orders'][1]['items'][0]['qty']);
    }

    public function test_exact_path_takes_precedence_over_wildcard()
    {
        $caster = Caster::make([
            'items.*.qty' => 'int',
            'items.0.qty' => 'string',
        ]);

        $result = $caster->cast([
            'items' => [
                ['qty' => '10'],
                ['qty' => '20'],
            ],
        ]);

        $this->assertSame('10', $result['items'][0]['qty']);
        $this->assertSame(20, $result['items'][1]['qty']);
    }

    public function test_wildcard_with_empty_array()
    {
        $caster = Caster::make(['items.*.qty' => 'int']);

        $result = $caster->cast(['items' => []]);

        $this->assertSame(['items' => []], $result);
    }

    public function test_custom_casts_value_implementation()
    {
        $result = Caster::make(['email' => new CasterTestEmailCast])
            ->cast(['email' => 'test@example.com']);

        $this->assertInstanceOf(CasterTestEmail::class, $result['email']);
        $this->assertSame('test@example.com', $result['email']->value);
    }

    public function test_castable_class()
    {
        $result = Caster::make(['money' => CasterTestMoney::class])
            ->cast(['money' => '100.00']);

        $this->assertInstanceOf(CasterTestMoney::class, $result['money']);
        $this->assertSame('100.00', $result['money']->amount);
    }

    public function test_eloquent_casts_attributes_implementation()
    {
        $result = Caster::make(['data' => new CasterTestEloquentStyleCast])
            ->cast(['data' => 'test-value']);

        $this->assertSame('CASTED:test-value', $result['data']);
    }

    public function test_invalid_cast_type_throws_exception()
    {
        $this->expectException(InvalidCastException::class);
        $this->expectExceptionMessage('Invalid cast specification');

        Caster::value('test', 'nonexistent_type');
    }

    public function test_class_without_interface_throws_exception()
    {
        $this->expectException(InvalidCastException::class);
        $this->expectExceptionMessage('must implement CastsValue, Castable, CastsAttributes, or be an enum');

        Caster::value('test', CasterTestPlainClass::class);
    }

    public function test_only_defined_paths_are_cast()
    {
        $result = Caster::make(['age' => 'int'])
            ->cast(['age' => '25', 'name' => 'John']);

        $this->assertSame(25, $result['age']);
        $this->assertSame('John', $result['name']);
    }

    public function test_missing_paths_are_ignored()
    {
        $result = Caster::make(['age' => 'int', 'name' => 'string'])
            ->cast(['name' => 'John']);

        $this->assertArrayNotHasKey('age', $result);
        $this->assertSame('John', $result['name']);
    }

    public function test_get_method()
    {
        $caster = Caster::make(['age' => 'int']);

        $this->assertSame(25, $caster->get(['age' => '25'], 'age'));
        $this->assertNull($caster->get(['age' => '25'], 'missing'));
        $this->assertSame('default', $caster->get(['age' => '25'], 'missing', 'default'));
    }

    public function test_only_method()
    {
        $caster = Caster::make([
            'age' => 'int',
            'name' => 'string',
            'active' => 'bool',
        ]);

        $result = $caster->only(
            ['age' => '25', 'name' => 'John', 'active' => '1'],
            ['age', 'name']
        );

        $this->assertSame(['age' => 25, 'name' => 'John'], $result);
        $this->assertArrayNotHasKey('active', $result);
    }

    public function test_casts_method_merges_definitions()
    {
        $caster = Caster::make(['age' => 'int']);
        $caster->casts(['name' => 'string']);

        $this->assertSame(['age' => 'int', 'name' => 'string'], $caster->getCasts());
    }

    public function test_caster_can_be_reused()
    {
        $caster = Caster::make(['value' => 'int']);

        $result1 = $caster->cast(['value' => '10']);
        $result2 = $caster->cast(['value' => '20']);

        $this->assertSame(10, $result1['value']);
        $this->assertSame(20, $result2['value']);
    }

    public function test_empty_data_returns_empty_array()
    {
        $caster = Caster::make(['value' => 'int']);

        $result = $caster->cast([]);

        $this->assertSame([], $result);
    }

    public function test_encrypted_cast()
    {
        $encrypter = m::mock(Encrypter::class);
        $encrypter->expects('encryptString')->with('secret')->andReturn('encrypted');
        Crypt::swap($encrypter);

        $this->assertSame('encrypted', Caster::value('secret', 'encrypted'));
    }

    public function test_encrypted_array_cast()
    {
        $encrypter = m::mock(Encrypter::class);
        $encrypter->expects('encryptString')->with('{"foo":"bar"}')->andReturn('encrypted-json');
        Crypt::swap($encrypter);

        $this->assertSame('encrypted-json', Caster::value(['foo' => 'bar'], 'encrypted:array'));
    }

    public function test_decrypted_cast()
    {
        $encrypter = m::mock(Encrypter::class);
        $encrypter->expects('decryptString')->with('encrypted')->andReturn('decrypted');
        Crypt::swap($encrypter);

        $this->assertSame('decrypted', Caster::value('encrypted', 'decrypted'));
    }

    public function test_decrypted_array_cast()
    {
        $encrypter = m::mock(Encrypter::class);
        $encrypter->expects('decryptString')->with('encrypted')->andReturn('{"foo":"bar"}');
        Crypt::swap($encrypter);

        $this->assertSame(['foo' => 'bar'], Caster::value('encrypted', 'decrypted:array'));
    }

    public function test_decrypted_collection_cast()
    {
        $encrypter = m::mock(Encrypter::class);
        $encrypter->expects('decryptString')->with('encrypted')->andReturn('[1,2,3]');
        Crypt::swap($encrypter);

        $result = Caster::value('encrypted', 'decrypted:collection');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame([1, 2, 3], $result->all());
    }

    public function test_hashed_cast()
    {
        $hasher = m::mock('stdClass');
        $hasher->expects('isHashed')->with('password')->andReturn(false);
        $hasher->expects('make')->with('password')->andReturn('hashed');
        Hash::swap($hasher);

        $this->assertSame('hashed', Caster::value('password', 'hashed'));
    }

    public function test_hashed_cast_skips_already_hashed()
    {
        $hasher = m::mock('stdClass');
        $hasher->expects('isHashed')->with('$2y$10$already')->andReturn(true);
        $hasher->expects('verifyConfiguration')->with('$2y$10$already')->andReturn(true);
        Hash::swap($hasher);

        $this->assertSame('$2y$10$already', Caster::value('$2y$10$already', 'hashed'));
    }

    public function test_hashed_cast_throws_on_invalid_configuration()
    {
        $hasher = m::mock('stdClass');
        $hasher->expects('isHashed')->with('$2y$10$outdated')->andReturn(true);
        $hasher->expects('verifyConfiguration')->with('$2y$10$outdated')->andReturn(false);
        Hash::swap($hasher);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Could not verify the hashed value's configuration.");

        Caster::value('$2y$10$outdated', 'hashed');
    }

    public function test_hashed_cast_preserves_null()
    {
        $result = Caster::make(['password' => 'hashed'])->cast(['password' => null]);

        $this->assertNull($result['password']);
    }

    public function test_deeply_nested_path_without_wildcard()
    {
        $caster = Caster::make(['a.b.c.d' => 'int']);

        $result = $caster->cast(['a' => ['b' => ['c' => ['d' => '42']]]]);

        $this->assertSame(42, $result['a']['b']['c']['d']);
    }

    public function test_cast_with_class_arguments()
    {
        $caster = Caster::make(['email' => CasterTestEmailCastWithPrefix::class.':PREFIX']);

        $result = $caster->cast(['email' => 'test@example.com']);

        $this->assertSame('PREFIX:test@example.com', $result['email']->value);
    }

    public function test_cast_unit_enum()
    {
        $result = Caster::value('Read', CasterTestPermission::class);

        $this->assertSame(CasterTestPermission::Read, $result);
    }

    public function test_cast_unit_enum_passthrough()
    {
        $result = Caster::value(CasterTestPermission::Write, CasterTestPermission::class);

        $this->assertSame(CasterTestPermission::Write, $result);
    }

    public function test_cast_datetime_from_timestamp()
    {
        $timestamp = 1736956200;
        $result = Caster::value($timestamp, 'datetime');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame($timestamp, $result->getTimestamp());
    }

    public function test_cast_datetime_from_carbon_instance()
    {
        $carbon = Carbon::parse('2026-01-15 14:30:00');
        $result = Caster::value($carbon, 'datetime');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame('2026-01-15 14:30:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_cast_empty_string_to_array_returns_empty()
    {
        $this->assertSame([], Caster::value('', 'array'));
    }

    public function test_cast_empty_string_to_object_returns_empty_object()
    {
        $result = Caster::value('', 'object');

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertSame([], (array) $result);
    }

    public function test_bool_cast_differs_from_eloquent()
    {
        // Eloquent does simple (bool) cast, Caster handles string truthy values
        $this->assertTrue(Caster::value('true', 'bool'));
        $this->assertTrue(Caster::value('on', 'bool'));
        $this->assertTrue(Caster::value('yes', 'bool'));
        $this->assertFalse(Caster::value('false', 'bool'));
        $this->assertFalse(Caster::value('off', 'bool'));
        $this->assertFalse(Caster::value('no', 'bool'));
    }

    public function test_multiple_wildcard_levels()
    {
        $caster = Caster::make(['*.*.value' => 'int']);

        $result = $caster->cast([
            'a' => ['x' => ['value' => '1'], 'y' => ['value' => '2']],
            'b' => ['z' => ['value' => '3']],
        ]);

        $this->assertSame(1, $result['a']['x']['value']);
        $this->assertSame(2, $result['a']['y']['value']);
        $this->assertSame(3, $result['b']['z']['value']);
    }

    public function test_castable_with_arguments()
    {
        $result = Caster::make(['amount' => CasterTestMoneyWithCurrency::class.':USD'])
            ->cast(['amount' => '100.00']);

        $this->assertInstanceOf(CasterTestMoneyWithCurrency::class, $result['amount']);
        $this->assertSame('100.00', $result['amount']->amount);
        $this->assertSame('USD', $result['amount']->currency);
    }

    public function test_caster_receives_full_data_context()
    {
        $caster = Caster::make(['derived' => new CasterTestContextAwareCast]);

        $result = $caster->cast([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'derived' => 'placeholder',
        ]);

        $this->assertSame('John Doe', $result['derived']);
    }

    public function test_decimal_cast_throws_on_invalid_value()
    {
        $this->expectException(\Illuminate\Support\Exceptions\MathException::class);

        Caster::value('not-a-number', 'decimal:2');
    }

    public function test_global_date_format()
    {
        Caster::useDateFormat('d/m/Y');

        $result = Caster::value('15/01/2026', 'date');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame('2026-01-15', $result->format('Y-m-d'));
    }

    public function test_instance_date_format()
    {
        $result = Caster::make(['date' => 'datetime'])
            ->dateFormat('d/m/Y H:i')
            ->cast(['date' => '15/01/2026 14:30']);

        $this->assertSame('2026-01-15 14:30', $result['date']->format('Y-m-d H:i'));
    }

    public function test_instance_date_format_overrides_global()
    {
        Caster::useDateFormat('Y-m-d');

        $result = Caster::make(['date' => 'date'])
            ->dateFormat('d/m/Y')
            ->cast(['date' => '15/01/2026']);

        $this->assertSame('2026-01-15', $result['date']->format('Y-m-d'));
    }

    public function test_inline_date_format_overrides_instance()
    {
        $result = Caster::make(['date' => 'datetime:m-d-Y H:i'])
            ->dateFormat('d/m/Y H:i')
            ->cast(['date' => '01-15-2026 14:30']);

        $this->assertSame('2026-01-15 14:30', $result['date']->format('Y-m-d H:i'));
    }

    public function test_date_format_applies_to_immutable_dates()
    {
        Caster::useDateFormat('d/m/Y');

        $result = Caster::value('15/01/2026', 'immutable_date');

        $this->assertInstanceOf(\Carbon\CarbonImmutable::class, $result);
        $this->assertSame('2026-01-15', $result->format('Y-m-d'));
    }
}

enum CasterTestStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
}

class CasterTestEmail
{
    public function __construct(public string $value)
    {
    }
}

class CasterTestEmailCast implements CastsValue
{
    public function cast(mixed $value, string $key, array $attributes)
    {
        return new CasterTestEmail($value);
    }
}

class CasterTestEmailCastWithPrefix implements CastsValue
{
    public function __construct(private string $prefix)
    {
    }

    public function cast(mixed $value, string $key, array $attributes)
    {
        return new CasterTestEmail($this->prefix.':'.$value);
    }
}

class CasterTestMoney implements Castable
{
    public function __construct(public string $amount)
    {
    }

    public static function castUsing(array $arguments)
    {
        return new class implements CastsValue
        {
            public function cast(mixed $value, string $key, array $attributes)
            {
                return new CasterTestMoney($value);
            }
        };
    }
}

class CasterTestEloquentStyleCast implements CastsAttributes
{
    public function get($model, string $key, mixed $value, array $attributes)
    {
        return 'CASTED:'.$value;
    }

    public function set($model, string $key, mixed $value, array $attributes)
    {
        return $value;
    }
}

class CasterTestPlainClass
{
    public function __construct(public string $value)
    {
    }
}

enum CasterTestPermission
{
    case Read;
    case Write;
    case Delete;
}

class CasterTestMoneyWithCurrency implements Castable
{
    public function __construct(public string $amount, public string $currency)
    {
    }

    public static function castUsing(array $arguments)
    {
        return new class($arguments[0] ?? 'USD') implements CastsValue
        {
            public function __construct(private string $currency)
            {
            }

            public function cast(mixed $value, string $key, array $attributes)
            {
                return new CasterTestMoneyWithCurrency($value, $this->currency);
            }
        };
    }
}

class CasterTestContextAwareCast implements CastsValue
{
    public function cast(mixed $value, string $key, array $attributes)
    {
        return ($attributes['first_name'] ?? '').' '.($attributes['last_name'] ?? '');
    }
}
