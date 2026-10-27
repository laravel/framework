<?php

namespace Illuminate\Tests\Support\Concerns;

use Carbon\CarbonInterval;
use Carbon\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Illuminate\Tests\Support\Fixtures\TestEnum;
use Illuminate\Tests\Support\Fixtures\TestIntegerBackedEnum;
use Illuminate\Tests\Support\Fixtures\TestStringBackedEnum;
use InvalidArgumentException;

include_once dirname(__DIR__).'/Fixtures/Enums.php';

trait TestsInteractsWithDataContract
{
    abstract protected static function arrayToDataFactory(array $data);

    protected function assertDataExistsContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'surname' => 'Otwell', 'age' => '']);

        $this->assertTrue($data->exists('name'));
        $this->assertTrue($data->exists('age'));
        $this->assertTrue($data->exists(['name', 'surname']));
        $this->assertFalse($data->exists('votes'));
        $this->assertFalse($data->exists(['name', 'votes']));
    }

    protected function assertDataExistsNestedContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'foo' => ['bar' => 'bar', 'baz' => '']]);

        $this->assertTrue($data->exists('foo'));
        $this->assertTrue($data->exists('foo.bar'));
        $this->assertTrue($data->exists(['name', 'foo.baz']));
        $this->assertFalse($data->exists(['name', 'foo.bax']));
    }

    protected function assertDataHasContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '', 'city' => '   ']);
        $this->assertTrue($data->has('name'));
        $this->assertTrue($data->has('age'));
        $this->assertTrue($data->has('city'));
        $this->assertTrue($data->has(['name']));
        $this->assertFalse($data->has('foo'));
        $this->assertFalse($data->has(['foo']));
        $this->assertFalse($data->has('name', 'email'));
        $this->assertFalse($data->has(['name', 'email']));

        $data = static::arrayToDataFactory(['name' => 'Taylor', 'email' => 'foo']);
        $this->assertTrue($data->has('name', 'email'));
        $this->assertTrue($data->has(['name', 'email']));

        $data = static::arrayToDataFactory(['foo' => ['bar', 'bar']]);
        $this->assertTrue($data->has('foo'));
    }

    protected function assertDataHasNestedContract()
    {
        $data = static::arrayToDataFactory(['foo' => ['bar' => 'bar', 'baz' => '']]);

        $this->assertTrue($data->has('foo'));
        $this->assertTrue($data->has('foo.bar'));
        $this->assertTrue($data->has('foo.baz'));
        $this->assertFalse($data->has('foo.bax'));
        $this->assertFalse($data->has(['foo.bar', 'foo.bax']));
    }

    protected function assertDataHasAnyContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '', 'city' => '   ']);
        $this->assertTrue($data->hasAny('name'));
        $this->assertTrue($data->hasAny('age'));
        $this->assertTrue($data->hasAny('city'));
        $this->assertFalse($data->hasAny('foo'));
        $this->assertTrue($data->hasAny('name', 'email'));
        $this->assertTrue($data->hasAny(['name', 'email']));
        $this->assertFalse($data->hasAny('surname', 'password'));
        $this->assertFalse($data->hasAny(['surname', 'password']));
    }

    protected function assertDataHasAnyNestedContract()
    {
        $data = static::arrayToDataFactory(['foo' => ['bar' => 'bar', 'baz' => '']]);

        $this->assertTrue($data->hasAny('foo.bar'));
        $this->assertTrue($data->hasAny('foo.baz'));
        $this->assertFalse($data->hasAny('foo.bax'));
        $this->assertTrue($data->hasAny(['foo.bax', 'foo.baz']));
        $this->assertTrue($data->hasAny(['votes', 'foo']));
    }

    protected function assertDataWhenHasContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '', 'city' => '   ']);

        $name = $age = $city = $foo = $bar = false;

        $data->whenHas('name', function ($value) use (&$name) {
            $name = $value;
        });

        $data->whenHas('age', function ($value) use (&$age) {
            $age = $value;
        });

        $data->whenHas('city', function ($value) use (&$city) {
            $city = $value;
        });

        $data->whenHas('foo', function () use (&$foo) {
            $foo = 'test';
        });

        $data->whenHas('bar', function () use (&$bar) {
            $bar = 'test';
        }, function () use (&$bar) {
            $bar = true;
        });

        $this->assertSame('Taylor', $name);
        $this->assertSame('', $age);
        $this->assertSame('   ', $city);
        $this->assertFalse($foo);
        $this->assertTrue($bar);

        $this->assertSame('callback-Taylor', $data->whenHas('name', fn ($value) => 'callback-'.$value));
        $this->assertSame($data, $data->whenHas('name', fn () => null));
        $this->assertSame($data, $data->whenHas('missing', fn ($value) => 'callback-'.$value));
        $this->assertSame('default', $data->whenHas('missing', fn ($value) => 'callback-'.$value, fn () => 'default'));
    }

    protected function assertDataWhenHasNestedContract()
    {
        $data = static::arrayToDataFactory(['foo' => ['bar' => '']]);

        $foo = $bar = $baz = false;

        $data->whenHas('foo', function ($value) use (&$foo) {
            $foo = $value;
        });

        $data->whenHas('foo.bar', function ($value) use (&$bar) {
            $bar = $value;
        });

        $data->whenHas('foo.baz', function () use (&$baz) {
            $baz = 'test';
        });

        $this->assertEquals(['bar' => ''], $foo);
        $this->assertSame('', $bar);
        $this->assertFalse($baz);
    }

    protected function assertDataFilledContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '', 'city' => '   ']);
        $this->assertTrue($data->filled('name'));
        $this->assertFalse($data->filled('age'));
        $this->assertFalse($data->filled('city'));
        $this->assertFalse($data->filled('foo'));
        $this->assertFalse($data->filled('name', 'email'));
        $this->assertFalse($data->filled(['name', 'email']));

        $data = static::arrayToDataFactory(['name' => 'Taylor', 'email' => 'foo']);
        $this->assertTrue($data->filled('name', 'email'));
        $this->assertTrue($data->filled(['name', 'email']));

        $data = static::arrayToDataFactory(['zero' => '0', 'false' => false, 'whitespace' => '   ']);
        $this->assertTrue($data->filled('zero'));
        $this->assertTrue($data->filled('false'));
        $this->assertFalse($data->filled('whitespace'));

        $data = static::arrayToDataFactory(['foo' => ['bar', 'baz']]);
        $this->assertTrue($data->filled('foo'));
    }

    protected function assertDataFilledNestedContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'foo' => ['bar' => '   ', 'baz' => '', 'bax' => 'bax']]);

        $this->assertTrue($data->filled(['name', 'foo']));
        $this->assertTrue($data->filled('foo.bax'));
        $this->assertFalse($data->filled('foo.bar'));
        $this->assertFalse($data->filled(['name', 'foo.baz']));
    }

    protected function assertDataIsNotFilledContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '', 'city' => '0', 'whitespace' => '   ']);
        $this->assertFalse($data->isNotFilled('name'));
        $this->assertTrue($data->isNotFilled('age'));
        $this->assertFalse($data->isNotFilled('city'));
        $this->assertTrue($data->isNotFilled('whitespace'));
        $this->assertTrue($data->isNotFilled('foo'));
        $this->assertFalse($data->isNotFilled(['name', 'email']));
        $this->assertFalse($data->isNotFilled('name', 'email'));
        $this->assertTrue($data->isNotFilled(['foo', 'age']));
        $this->assertTrue($data->isNotFilled(['age', 'whitespace']));
        $this->assertTrue($data->isNotFilled('age', 'whitespace'));
        $this->assertFalse($data->isNotFilled('age', 'city'));
    }

    protected function assertDataIsNotFilledNestedContract()
    {
        $data = static::arrayToDataFactory(['foo' => ['bar', 'baz' => '0', 'bax' => '']]);

        $this->assertFalse($data->isNotFilled('foo'));
        $this->assertFalse($data->isNotFilled('foo.baz'));
        $this->assertTrue($data->isNotFilled('foo.bar'));
        $this->assertTrue($data->isNotFilled('foo.bax'));
    }

    protected function assertDataAnyFilledContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '', 'city' => '   ']);

        $this->assertTrue($data->anyFilled(['name']));
        $this->assertTrue($data->anyFilled('name'));

        $this->assertFalse($data->anyFilled(['age']));
        $this->assertFalse($data->anyFilled('age'));

        $this->assertFalse($data->anyFilled(['foo']));
        $this->assertFalse($data->anyFilled('foo'));

        $this->assertTrue($data->anyFilled(['age', 'name']));
        $this->assertTrue($data->anyFilled('age', 'name'));

        $this->assertTrue($data->anyFilled(['foo', 'name']));
        $this->assertTrue($data->anyFilled('foo', 'name'));

        $this->assertFalse($data->anyFilled(['age', 'city']));
        $this->assertFalse($data->anyFilled('age', 'city'));

        $this->assertFalse($data->anyFilled(['foo', 'bar']));
        $this->assertFalse($data->anyFilled('foo', 'bar'));
    }

    protected function assertDataAnyFilledNestedContract()
    {
        $data = static::arrayToDataFactory(['foo' => ['bar' => '', 'baz' => 'baz']]);

        $this->assertTrue($data->anyFilled('foo'));
        $this->assertTrue($data->anyFilled(['foo.bar', 'foo.baz']));
        $this->assertFalse($data->anyFilled('foo.bar'));
    }

    protected function assertDataWhenFilledContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '', 'city' => '   ']);

        $name = $age = $city = $foo = $bar = false;

        $data->whenFilled('name', function ($value) use (&$name) {
            $name = $value;
        });

        $data->whenFilled('age', function () use (&$age) {
            $age = 'test';
        });

        $data->whenFilled('city', function () use (&$city) {
            $city = 'test';
        });

        $data->whenFilled('foo', function () use (&$foo) {
            $foo = 'test';
        });

        $data->whenFilled('bar', function () use (&$bar) {
            $bar = 'test';
        }, function () use (&$bar) {
            $bar = true;
        });

        $this->assertSame('Taylor', $name);
        $this->assertFalse($age);
        $this->assertFalse($city);
        $this->assertFalse($foo);
        $this->assertTrue($bar);

        $this->assertSame('callback-Taylor', $data->whenFilled('name', fn ($value) => 'callback-'.$value));
        $this->assertSame($data, $data->whenFilled('name', fn () => null));
        $this->assertSame($data, $data->whenFilled('age', fn ($value) => 'callback-'.$value));
        $this->assertSame('default', $data->whenFilled('age', fn ($value) => 'callback-'.$value, fn () => 'default'));
    }

    protected function assertDataWhenFilledNestedContract()
    {
        $data = static::arrayToDataFactory(['foo' => ['bar' => '', 'baz' => 'baz']]);

        $foo = $bar = $baz = false;

        $data->whenFilled('foo', function ($value) use (&$foo) {
            $foo = $value;
        });

        $data->whenFilled('foo.bar', function () use (&$bar) {
            $bar = 'test';
        });

        $data->whenFilled('foo.baz', function ($value) use (&$baz) {
            $baz = $value;
        });

        $this->assertEquals(['bar' => '', 'baz' => 'baz'], $foo);
        $this->assertFalse($bar);
        $this->assertSame('baz', $baz);
    }

    protected function assertDataWhenEnumContract()
    {
        $data = static::arrayToDataFactory(['status' => 'A', 'invalid' => 'invalid', 'empty' => '']);

        $status = $invalid = $empty = $unit = $missing = $default = false;

        $data->whenEnum('status', TestStringBackedEnum::class, function ($value) use (&$status) {
            $status = $value;
        });

        $data->whenEnum('invalid', TestStringBackedEnum::class, function ($value) use (&$invalid) {
            $invalid = $value;
        });

        $data->whenEnum('empty', TestStringBackedEnum::class, function ($value) use (&$empty) {
            $empty = $value;
        });

        $data->whenEnum('status', TestEnum::class, function ($value) use (&$unit) {
            $unit = $value;
        });

        $data->whenEnum('missing', TestStringBackedEnum::class, function ($value) use (&$missing) {
            $missing = $value;
        }, function () use (&$default) {
            $default = true;
        });

        $this->assertSame(TestStringBackedEnum::A, $status);
        $this->assertFalse($invalid);
        $this->assertFalse($empty);
        $this->assertFalse($unit);
        $this->assertFalse($missing);
        $this->assertTrue($default);

        $this->assertSame('callback-A', $data->whenEnum('status', TestStringBackedEnum::class, fn ($value) => 'callback-'.$value->value));
        $this->assertSame($data, $data->whenEnum('status', TestStringBackedEnum::class, fn () => null));
        $this->assertSame($data, $data->whenEnum('missing', TestStringBackedEnum::class, fn () => 'callback'));
        $this->assertSame('default', $data->whenEnum('missing', TestStringBackedEnum::class, fn () => 'callback', fn () => 'default'));
    }

    protected function assertDataMissingContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '', 'city' => '   ']);
        $this->assertFalse($data->missing('name'));
        $this->assertFalse($data->missing('age'));
        $this->assertFalse($data->missing('city'));
        $this->assertTrue($data->missing('foo'));
        $this->assertTrue($data->missing(['foo']));
        $this->assertTrue($data->missing('name', 'email'));
        $this->assertTrue($data->missing(['name', 'email']));

        $data = static::arrayToDataFactory(['name' => 'Taylor', 'email' => 'foo']);
        $this->assertFalse($data->missing('name', 'email'));
        $this->assertFalse($data->missing(['name', 'email']));
    }

    protected function assertDataMissingNestedContract()
    {
        $data = static::arrayToDataFactory(['foo' => ['bar' => 'bar', 'baz' => '']]);

        $this->assertFalse($data->missing('foo'));
        $this->assertFalse($data->missing('foo.bar'));
        $this->assertFalse($data->missing('foo.baz'));
        $this->assertTrue($data->missing('foo.bax'));
        $this->assertTrue($data->missing(['foo.bar', 'foo.bax']));
    }

    protected function assertDataWhenMissingContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'bar' => '']);

        $age = $city = $name = $bar = false;

        $data->whenMissing('age', function () use (&$age) {
            $age = '';
        });

        $data->whenMissing('city', function ($value) use (&$city) {
            $city = $value;
        });

        $data->whenMissing('name', function () use (&$name) {
            $name = 'test';
        });

        $data->whenMissing('bar', function () use (&$bar) {
            $bar = 'test';
        }, function () use (&$bar) {
            $bar = true;
        });

        $this->assertSame('', $age);
        $this->assertNull($city);
        $this->assertFalse($name);
        $this->assertTrue($bar);

        $this->assertSame('callback', $data->whenMissing('missing', fn () => 'callback'));
        $this->assertSame($data, $data->whenMissing('missing', fn () => null));
        $this->assertSame($data, $data->whenMissing('name', fn () => 'callback'));
        $this->assertSame('default', $data->whenMissing('name', fn () => 'callback', fn () => 'default'));
    }

    protected function assertDataWhenMissingNestedContract()
    {
        $data = static::arrayToDataFactory(['foo' => ['bar' => '']]);

        $foo = $bar = $baz = false;

        $data->whenMissing('foo', function () use (&$foo) {
            $foo = 'test';
        });

        $data->whenMissing('foo.bar', function () use (&$bar) {
            $bar = 'test';
        });

        $data->whenMissing('foo.baz', function () use (&$baz) {
            $baz = true;
        });

        $this->assertFalse($foo);
        $this->assertFalse($bar);
        $this->assertTrue($baz);
    }

    protected function assertDataStrContract()
    {
        $data = static::arrayToDataFactory([
            'int' => 123,
            'str' => 'abc',
            'empty_str' => '',
        ]);

        $this->assertInstanceOf(Stringable::class, $data->str('int'));
        $this->assertInstanceOf(Stringable::class, $data->str('unknown_key'));
        $this->assertSame('123', $data->str('int')->value());
        $this->assertSame('abc', $data->str('str')->value());
        $this->assertSame('', $data->str('empty_str')->value());
        $this->assertSame('', $data->str('unknown_key')->value());
        $this->assertSame('default', $data->str('unknown_key', 'default')->value());
    }

    protected function assertDataStringContract()
    {
        $data = static::arrayToDataFactory([
            'int' => 123,
            'int_str' => '456',
            'float' => 123.456,
            'float_str' => '123.456',
            'float_str_zero' => '0.000',
            'str' => 'abc',
            'empty_str' => '',
        ]);

        $this->assertInstanceOf(Stringable::class, $data->string('int'));
        $this->assertInstanceOf(Stringable::class, $data->string('unknown_key'));
        $this->assertSame('123', $data->string('int')->value());
        $this->assertSame('456', $data->string('int_str')->value());
        $this->assertSame('123.456', $data->string('float')->value());
        $this->assertSame('123.456', $data->string('float_str')->value());
        $this->assertSame('0.000', $data->string('float_str_zero')->value());
        $this->assertSame('abc', $data->string('str')->value());
        $this->assertSame('', $data->string('empty_str')->value());
        $this->assertSame('', $data->string('unknown_key')->value());
        $this->assertSame('default', $data->string('unknown_key', 'default')->value());
    }

    protected function assertDataBooleanContract()
    {
        $data = static::arrayToDataFactory([
            'with_trashed' => 'false',
            'download' => true,
            'checked' => 1,
            'unchecked' => '0',
            'with_on' => 'on',
            'with_off' => 'off',
            'with_yes' => 'yes',
            'with_no' => 'no',
            'with_true' => 'true',
        ]);

        $this->assertTrue($data->boolean('checked'));
        $this->assertTrue($data->boolean('download'));
        $this->assertFalse($data->boolean('unchecked'));
        $this->assertFalse($data->boolean('with_trashed'));
        $this->assertTrue($data->boolean('with_on'));
        $this->assertFalse($data->boolean('with_off'));
        $this->assertTrue($data->boolean('with_yes'));
        $this->assertFalse($data->boolean('with_no'));
        $this->assertTrue($data->boolean('with_true'));
        $this->assertFalse($data->boolean('some_undefined_key'));
        $this->assertTrue($data->boolean('some_undefined_key', true));
    }

    protected function assertDataIntegerContract()
    {
        $data = static::arrayToDataFactory([
            'int' => '123',
            'raw_int' => 456,
            'float' => '123.45',
            'zero_padded' => '078',
            'space_padded' => ' 901',
            'nan' => 'nan',
            'mixed' => '1ab',
            'underscore_notation' => '2_000',
        ]);

        $this->assertSame(123, $data->integer('int'));
        $this->assertSame(456, $data->integer('raw_int'));
        $this->assertSame(123, $data->integer('float'));
        $this->assertSame(78, $data->integer('zero_padded'));
        $this->assertSame(901, $data->integer('space_padded'));
        $this->assertSame(0, $data->integer('nan'));
        $this->assertSame(1, $data->integer('mixed'));
        $this->assertSame(2, $data->integer('underscore_notation'));
        $this->assertSame(0, $data->integer('unknown_key'));
        $this->assertSame(123456, $data->integer('unknown_key', 123456));
    }

    protected function assertDataFloatContract()
    {
        $data = static::arrayToDataFactory([
            'float' => '1.23',
            'raw_float' => 45.6,
            'int' => '123',
            'decimal_only' => '.6',
            'zero_padded' => '0.78',
            'space_padded' => ' 90.1',
            'nan' => 'nan',
            'mixed' => '1.ab',
            'scientific_notation' => '1e3',
        ]);

        $this->assertSame(1.23, $data->float('float'));
        $this->assertSame(45.6, $data->float('raw_float'));
        $this->assertSame(123.0, $data->float('int'));
        $this->assertSame(.6, $data->float('decimal_only'));
        $this->assertSame(0.78, $data->float('zero_padded'));
        $this->assertSame(90.1, $data->float('space_padded'));
        $this->assertSame(0.0, $data->float('nan'));
        $this->assertSame(1.0, $data->float('mixed'));
        $this->assertSame(1e3, $data->float('scientific_notation'));
        $this->assertSame(0.0, $data->float('unknown_key'));
        $this->assertSame(123.456, $data->float('unknown_key', 123.456));
    }

    protected function assertDataClampContract()
    {
        $data = static::arrayToDataFactory([
            'per_page' => 100,
            'float' => 9.24,
            'numeric_string' => '50',
            'empty' => '',
            'string' => 'string',
        ]);

        $this->assertEquals(100, $data->clamp('per_page', 100, 101));
        $this->assertEquals(10, $data->clamp('per_page', -10, 10));
        $this->assertSame(25, $data->clamp('per_page_2', 25, 100, 1));
        $this->assertEquals(100, $data->clamp('per_page', 1, 250, 99));
        $this->assertSame(22.4, $data->clamp('per_page', 1.11, 22.4, 2));
        $this->assertEquals(9.24, $data->clamp('float', 1, 10));
        $this->assertEquals(50, $data->clamp('numeric_string', 1, 100));
        $this->assertSame(15, $data->clamp('empty', 10, 100, 15));
        $this->assertSame(15, $data->clamp('string', 10, 100, 15));
    }

    protected function assertDataDateContract()
    {
        $data = static::arrayToDataFactory([
            'as_empty' => '',
            'as_datetime' => '20-01-01 16:30:25',
            'as_format' => '1577896225',
            'as_timezone' => '20-01-01 13:30:25',
            'as_date' => '2020-01-01',
            'as_time' => '16:30:25',
            'invalid' => 'invalid',
        ]);

        $current = Carbon::create(2020, 1, 1, 16, 30, 25);

        $this->assertNull($data->date('as_empty'));
        $this->assertNull($data->date('doesnt_exists'));

        $this->assertEquals($current, $data->date('as_datetime'));
        $this->assertEquals($current->format('Y-m-d H:i:s P'), $data->date('as_format', 'U')->format('Y-m-d H:i:s P'));
        $this->assertEquals($current, $data->date('as_timezone', null, 'America/Santiago'));

        $this->assertTrue($data->date('as_date')->isSameDay($current));
        $this->assertTrue($data->date('as_time')->isSameSecond('16:30:25'));

        try {
            $data->date('invalid');

            $this->fail('An invalid date value should throw an exception.');
        } catch (InvalidArgumentException) {
            //
        }

        try {
            $data->date('as_datetime', 'invalid_format');

            $this->fail('An invalid date format should throw an exception.');
        } catch (InvalidArgumentException) {
            //
        }
    }

    protected function assertDataIntervalContract()
    {
        $data = static::arrayToDataFactory([
            'as_empty' => '',
            'as_iso' => 'P1Y2M3DT4H5M6S',
            'as_human' => '2 hours 30 minutes',
            'as_seconds' => '90',
            'as_minutes' => '45',
            'small_float' => '0.000053',
            'very_small' => '0.000001',
            'scientific' => '5.3E-5',
            'normal_float' => '1.5',
        ]);

        $this->assertNull($data->interval('as_empty'));
        $this->assertNull($data->interval('doesnt_exist'));

        $interval = $data->interval('as_iso');
        $this->assertInstanceOf(CarbonInterval::class, $interval);
        $this->assertSame(1, $interval->years);
        $this->assertSame(2, $interval->months);
        $this->assertSame(3, $interval->dayz);
        $this->assertSame(4, $interval->hours);
        $this->assertSame(5, $interval->minutes);
        $this->assertSame(6, $interval->seconds);

        $interval = $data->interval('as_human');
        $this->assertInstanceOf(CarbonInterval::class, $interval);
        $this->assertSame(2, $interval->hours);
        $this->assertSame(30, $interval->minutes);

        $interval = $data->interval('as_seconds', 'second');
        $this->assertInstanceOf(CarbonInterval::class, $interval);
        $this->assertSame(90, $interval->seconds);

        $this->assertSame(90, $data->interval('as_seconds', 'minute')->minutes);
        $this->assertSame(90, $data->interval('as_seconds', 'hour')->hours);
        $this->assertSame(90, $data->interval('as_seconds', 'day')->dayz);

        $this->assertSame(45, $data->interval('as_minutes', Unit::Minute)->minutes);
        $this->assertSame(45, $data->interval('as_minutes', Unit::Second)->seconds);

        // Small floats that PHP would render in scientific notation (e.g. 5.3E-5)
        $this->assertInstanceOf(CarbonInterval::class, $data->interval('small_float', Unit::Millisecond));
        $this->assertInstanceOf(CarbonInterval::class, $data->interval('very_small', Unit::Second));
        $this->assertInstanceOf(CarbonInterval::class, $data->interval('scientific', Unit::Millisecond));
        $this->assertSame(1, $data->interval('normal_float', Unit::Hour)->hours);
    }

    protected function assertDataEnumContract()
    {
        $data = static::arrayToDataFactory([
            'valid_enum_value' => 'A',
            'invalid_enum_value' => 'invalid',
            'empty_value' => '',
            'minus_1' => '-1',
            'zero' => '0',
            'plus_1' => 1,
            'doesnt_exist' => '-1024',
        ]);

        $this->assertNull($data->enum('missing_key', TestStringBackedEnum::class));
        $this->assertNull($data->enum('missing_key', TestEnum::class));

        $this->assertSame(TestStringBackedEnum::A, $data->enum('valid_enum_value', TestStringBackedEnum::class));

        $this->assertNull($data->enum('invalid_enum_value', TestStringBackedEnum::class));
        $this->assertNull($data->enum('empty_value', TestStringBackedEnum::class));
        $this->assertNull($data->enum('valid_enum_value', TestEnum::class));

        $this->assertSame(TestStringBackedEnum::B, $data->enum('invalid_enum_value', TestStringBackedEnum::class, TestStringBackedEnum::B));
        $this->assertSame(TestStringBackedEnum::B, $data->enum('missing_key', TestStringBackedEnum::class, TestStringBackedEnum::B));
        $this->assertSame(TestStringBackedEnum::B, $data->enum('missing_key', TestStringBackedEnum::class, fn () => TestStringBackedEnum::B));

        $this->assertSame(TestIntegerBackedEnum::minus_1, $data->enum('minus_1', TestIntegerBackedEnum::class));
        $this->assertSame(TestIntegerBackedEnum::zero, $data->enum('zero', TestIntegerBackedEnum::class));
        $this->assertSame(TestIntegerBackedEnum::plus_1, $data->enum('plus_1', TestIntegerBackedEnum::class));
        $this->assertNull($data->enum('doesnt_exist', TestIntegerBackedEnum::class));
    }

    protected function assertDataEnumNestedContract()
    {
        $data = static::arrayToDataFactory([
            'string' => ['a' => 'A', 'zero' => '0'],
        ]);

        $this->assertSame(TestStringBackedEnum::A, $data->enum('string.a', TestStringBackedEnum::class));
        $this->assertSame(TestIntegerBackedEnum::zero, $data->enum('string.zero', TestIntegerBackedEnum::class));
        $this->assertNull($data->enum('string.missing', TestStringBackedEnum::class));
    }

    protected function assertDataEnumsContract()
    {
        $data = static::arrayToDataFactory([
            'valid_enum_values' => ['A', 'B'],
            'mixed_enum_values' => ['A', 'invalid'],
            'invalid_enum_values' => ['invalid', 'invalid'],
            'single_value' => 'B',
            'integer_values' => ['-1', '0', '1'],
        ]);

        $this->assertSame([], $data->enums('missing_key', TestStringBackedEnum::class));
        $this->assertSame([], $data->enums('valid_enum_values', TestEnum::class));

        $this->assertSame([TestStringBackedEnum::A, TestStringBackedEnum::B], $data->enums('valid_enum_values', TestStringBackedEnum::class));
        $this->assertSame([TestStringBackedEnum::A], $data->enums('mixed_enum_values', TestStringBackedEnum::class));
        $this->assertSame([], $data->enums('invalid_enum_values', TestStringBackedEnum::class));
        $this->assertSame([TestStringBackedEnum::B], $data->enums('single_value', TestStringBackedEnum::class));

        $this->assertSame([TestIntegerBackedEnum::minus_1, TestIntegerBackedEnum::zero, TestIntegerBackedEnum::plus_1], $data->enums('integer_values', TestIntegerBackedEnum::class));
    }

    protected function assertDataArrayContract()
    {
        $data = static::arrayToDataFactory(['users' => ['1', '2', '3'], 'roles' => ['4', '5', '6'], 'email' => 'test@example.com']);

        $this->assertSame(['1', '2', '3'], $data->array('users'));
        $this->assertSame(['test@example.com'], $data->array('email'));
        $this->assertSame([], $data->array('missing'));
        $this->assertSame([], $data->array(['missing']));
        $this->assertEquals(['roles' => ['4', '5', '6']], $data->array(['roles']));
        $this->assertEquals(['users' => ['1', '2', '3'], 'email' => 'test@example.com'], $data->array(['users', 'email']));
        $this->assertEquals(['users' => ['1', '2', '3'], 'roles' => ['4', '5', '6'], 'email' => 'test@example.com'], $data->array());

        $this->assertSame([], static::arrayToDataFactory([])->array());
    }

    protected function assertDataArrayArrayableContract()
    {
        $this->assertSame([1, 2], static::arrayToDataFactory(['users' => new Collection([1, 2])])->array('users'));
    }

    protected function assertDataCollectContract()
    {
        $data = static::arrayToDataFactory(['users' => ['1', '2', '3'], 'roles' => ['4', '5', '6'], 'email' => 'test@example.com']);

        $this->assertInstanceOf(Collection::class, $data->collect('users'));
        $this->assertSame(['1', '2', '3'], $data->collect('users')->all());
        $this->assertSame(['test@example.com'], $data->collect('email')->all());
        $this->assertTrue($data->collect('missing')->isEmpty());
        $this->assertInstanceOf(Collection::class, $data->collect(['users']));
        $this->assertTrue($data->collect(['missing'])->isEmpty());
        $this->assertEquals(['roles' => ['4', '5', '6']], $data->collect(['roles'])->all());
        $this->assertEquals(['users' => ['1', '2', '3'], 'email' => 'test@example.com'], $data->collect(['users', 'email'])->all());
        $this->assertEquals(['users' => ['1', '2', '3'], 'roles' => ['4', '5', '6'], 'email' => 'test@example.com'], $data->collect()->all());

        $this->assertInstanceOf(Collection::class, static::arrayToDataFactory([])->collect());
        $this->assertTrue(static::arrayToDataFactory([])->collect()->isEmpty());
    }

    protected function assertDataCollectNestedContract()
    {
        $data = static::arrayToDataFactory(['authors' => ['taylor' => ['products' => ['forge', 'vapor', 'spark']]]]);

        $this->assertSame(['forge', 'vapor', 'spark'], $data->collect('authors.taylor.products')->all());
    }

    protected function assertDataOnlyContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '', 'email' => 'taylor@laravel.com']);

        $this->assertEquals(['name' => 'Taylor'], $data->only('name'));
        $this->assertEquals(['name' => 'Taylor', 'age' => ''], $data->only('name', 'age', 'missing'));
        $this->assertEquals(['name' => 'Taylor', 'age' => ''], $data->only(['name', 'age', 'missing']));
        $this->assertSame([], $data->only('missing'));
    }

    protected function assertDataOnlyNestedContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'developer' => ['name' => 'Taylor', 'age' => '']]);

        $this->assertEquals(['developer' => ['name' => 'Taylor']], $data->only('developer.name', 'developer.skills'));
        $this->assertEquals(['developer' => ['age' => '']], $data->only('developer.age'));
        $this->assertEquals(['name' => 'Taylor', 'developer' => ['name' => 'Taylor', 'age' => '']], $data->only('name', 'developer'));
        $this->assertSame([], $data->only('developer.skills'));
    }

    protected function assertDataExceptContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'age' => '25']);

        $this->assertEquals(['name' => 'Taylor'], $data->except('age'));
        $this->assertEquals(['name' => 'Taylor', 'age' => '25'], $data->except('missing'));
        $this->assertSame([], $data->except('age', 'name'));
        $this->assertSame([], $data->except(['age', 'name']));
    }

    protected function assertDataExceptNestedContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'developer' => ['name' => 'Taylor', 'age' => '']]);

        $this->assertEquals(['name' => 'Taylor', 'developer' => ['name' => 'Taylor']], $data->except('developer.age'));
        $this->assertEquals(['developer' => ['age' => '']], $data->except('name', 'developer.name'));
    }

    protected function assertDataNullValuesContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'city' => null]);

        $this->assertTrue($data->exists('city'));
        $this->assertTrue($data->has('city'));
        $this->assertTrue($data->has('name', 'city'));
        $this->assertTrue($data->hasAny('city'));
        $this->assertFalse($data->missing('city'));

        $this->assertFalse($data->filled('city'));
        $this->assertTrue($data->isNotFilled('city'));
        $this->assertFalse($data->anyFilled('city'));
        $this->assertTrue($data->anyFilled('city', 'name'));

        $city = $missing = $filled = false;

        $data->whenHas('city', function ($value) use (&$city) {
            $city = $value;
        });

        $data->whenMissing('city', function () use (&$missing) {
            $missing = true;
        });

        $data->whenFilled('city', function () use (&$filled) {
            $filled = true;
        });

        $this->assertNull($city);
        $this->assertFalse($missing);
        $this->assertFalse($filled);

        $this->assertSame('', $data->string('city')->value());
        $this->assertFalse($data->boolean('city'));
        $this->assertSame(0, $data->integer('city'));
        $this->assertSame(0.0, $data->float('city'));
        $this->assertSame(15, $data->clamp('city', 10, 100, 15));
        $this->assertNull($data->date('city'));
        $this->assertNull($data->interval('city'));
        $this->assertNull($data->enum('city', TestStringBackedEnum::class));
        $this->assertSame([], $data->enums('city', TestStringBackedEnum::class));
    }

    protected function assertDataNullValuesIgnoreDefaultsContract()
    {
        $data = static::arrayToDataFactory(['city' => null]);

        $this->assertSame('', $data->string('city', 'default')->value());
        $this->assertFalse($data->boolean('city', true));
        $this->assertSame(0, $data->integer('city', 123456));
        $this->assertSame(0.0, $data->float('city', 123.456));
    }

    protected function assertDataNestedNullValuesContract()
    {
        $data = static::arrayToDataFactory(['name' => 'Taylor', 'foo' => ['bar' => null]]);

        $this->assertTrue($data->exists('foo.bar'));
        $this->assertTrue($data->has('foo.bar'));
        $this->assertTrue($data->hasAny('foo.bar'));
        $this->assertFalse($data->missing('foo.bar'));
        $this->assertTrue($data->filled('foo'));
        $this->assertFalse($data->filled('foo.bar'));
        $this->assertTrue($data->isNotFilled('foo.bar'));

        $bar = false;

        $data->whenHas('foo.bar', function ($value) use (&$bar) {
            $bar = $value;
        });

        $this->assertNull($bar);
        $this->assertSame(['foo' => ['bar' => null]], $data->only('foo.bar'));
        $this->assertSame(['name' => 'Taylor', 'foo' => ['bar' => null]], $data->only('name', 'foo.bar', 'foo.baz'));
        $this->assertSame(['foo' => ['bar' => null]], $data->except('name'));
    }
}
