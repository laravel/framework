<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Illuminate\Support\ValidatedInput;
use Illuminate\Tests\Support\Fixtures\StringBackedEnum;
use PHPUnit\Framework\TestCase;

class ValidatedInputTest extends TestCase
{
    public function test_can_access_input()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertSame('Taylor', $input->name);
        $this->assertSame('Taylor', $input['name']);
        $this->assertEquals(['name' => 'Taylor'], $input->all(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->only(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->except(['votes']));
        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->all());
    }

    public function test_can_merge_items()
    {
        $input = new ValidatedInput(['name' => 'Taylor']);

        $input = $input->merge(['votes' => 100]);

        $this->assertSame('Taylor', $input->name);
        $this->assertSame('Taylor', $input['name']);
        $this->assertEquals(['name' => 'Taylor'], $input->only(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->except(['votes']));
        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->all());
    }

    public function test_input_existence()
    {
        $inputA = new ValidatedInput(['name' => 'Taylor']);

        $this->assertTrue($inputA->has('name'));
        $this->assertTrue($inputA->missing('votes'));
        $this->assertTrue($inputA->missing(['votes']));
        $this->assertFalse($inputA->missing('name'));

        $inputB = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertTrue($inputB->has(['name', 'votes']));
    }

    public function test_exists_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertTrue($input->exists('name'));
        $this->assertTrue($input->exists('surname'));
        $this->assertTrue($input->exists(['name', 'surname']));
        $this->assertTrue($input->exists('foo.bar'));
        $this->assertTrue($input->exists(['name', 'foo.baz']));
        $this->assertTrue($input->exists(['name', 'foo']));
        $this->assertTrue($input->exists('foo'));

        $this->assertFalse($input->exists('votes'));
        $this->assertFalse($input->exists(['name', 'votes']));
        $this->assertFalse($input->exists(['votes', 'foo.bar']));
    }

    public function test_has_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertTrue($input->has('name'));
        $this->assertTrue($input->has('surname'));
        $this->assertTrue($input->has(['name', 'surname']));
        $this->assertTrue($input->has('foo.bar'));
        $this->assertTrue($input->has(['name', 'foo.baz']));
        $this->assertTrue($input->has(['name', 'foo']));
        $this->assertTrue($input->has('foo'));

        $this->assertFalse($input->has('votes'));
        $this->assertFalse($input->has(['name', 'votes']));
        $this->assertFalse($input->has(['votes', 'foo.bar']));
    }

    public function test_has_any_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertTrue($input->hasAny('name'));
        $this->assertTrue($input->hasAny('surname'));
        $this->assertTrue($input->hasAny('foo.bar'));
        $this->assertTrue($input->hasAny(['name', 'surname']));
        $this->assertTrue($input->hasAny(['name', 'foo.bat']));
        $this->assertTrue($input->hasAny(['votes', 'foo']));

        $this->assertFalse($input->hasAny('votes'));
        $this->assertFalse($input->hasAny(['votes', 'foo.bat']));
    }

    public function test_when_has_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'age' => '', 'foo' => ['bar' => null]]);

        $name = $age = $city = $foo = $bar = $baz = false;

        $input->whenHas('name', function ($value) use (&$name) {
            $name = $value;
        });

        $input->whenHas('age', function ($value) use (&$age) {
            $age = $value;
        });

        $input->whenHas('city', function ($value) use (&$city) {
            $city = $value;
        });

        $input->whenHas('foo', function ($value) use (&$foo) {
            $foo = $value;
        });

        $input->whenHas('foo.bar', function ($value) use (&$bar) {
            $bar = $value;
        });

        $input->whenHas('foo.baz', function () use (&$baz) {
            $baz = 'test';
        }, function () use (&$baz) {
            $baz = true;
        });

        $this->assertSame('Fatih', $name);
        $this->assertSame('', $age);
        $this->assertFalse($city);
        $this->assertEquals(['bar' => null], $foo);
        $this->assertTrue($baz);
        $this->assertNull($bar);
    }

    public function test_filled_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertTrue($input->filled('name'));
        $this->assertTrue($input->filled('surname'));
        $this->assertTrue($input->filled(['name', 'surname']));
        $this->assertTrue($input->filled(['name', 'foo']));
        $this->assertTrue($input->filled('foo'));

        $this->assertFalse($input->filled('foo.bar'));
        $this->assertFalse($input->filled(['name', 'foo.baz']));
        $this->assertFalse($input->filled('votes'));
        $this->assertFalse($input->filled(['name', 'votes']));
        $this->assertFalse($input->filled(['votes', 'foo.bar']));
    }

    public function test_is_not_filled_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertFalse($input->isNotFilled('name'));
        $this->assertFalse($input->isNotFilled('surname'));
        $this->assertFalse($input->isNotFilled(['name', 'surname']));
        $this->assertFalse($input->isNotFilled(['name', 'foo']));
        $this->assertFalse($input->isNotFilled('foo'));
        $this->assertFalse($input->isNotFilled(['name', 'foo.baz']));
        $this->assertFalse($input->isNotFilled(['name', 'votes']));

        $this->assertTrue($input->isNotFilled('foo.bar'));
        $this->assertTrue($input->isNotFilled('votes'));
        $this->assertTrue($input->isNotFilled(['votes', 'foo.bar']));
    }

    public function test_any_filled_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertTrue($input->anyFilled('name'));
        $this->assertTrue($input->anyFilled('surname'));
        $this->assertTrue($input->anyFilled(['name', 'surname']));
        $this->assertTrue($input->anyFilled(['name', 'foo']));
        $this->assertTrue($input->anyFilled('foo'));
        $this->assertTrue($input->anyFilled(['name', 'foo.baz']));
        $this->assertTrue($input->anyFilled(['name', 'votes']));

        $this->assertFalse($input->anyFilled('foo.bar'));
        $this->assertFalse($input->anyFilled('votes'));
        $this->assertFalse($input->anyFilled(['votes', 'foo.bar']));
    }

    public function test_when_filled_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'age' => '', 'foo' => ['bar' => null]]);

        $name = $age = $city = $foo = $bar = $baz = false;

        $input->whenFilled('name', function ($value) use (&$name) {
            $name = $value;
        });

        $input->whenFilled('age', function ($value) use (&$age) {
            $age = $value;
        });

        $input->whenFilled('city', function ($value) use (&$city) {
            $city = $value;
        });

        $input->whenFilled('foo', function ($value) use (&$foo) {
            $foo = $value;
        });

        $input->whenFilled('foo.bar', function ($value) use (&$bar) {
            $bar = $value;
        });

        $input->whenFilled('foo.baz', function () use (&$baz) {
            $baz = 'test';
        }, function () use (&$baz) {
            $baz = true;
        });

        $this->assertSame('Fatih', $name);
        $this->assertEquals(['bar' => null], $foo);
        $this->assertTrue($baz);
        $this->assertFalse($age);
        $this->assertFalse($city);
        $this->assertFalse($bar);
    }

    public function test_missing_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertFalse($input->missing('name'));
        $this->assertFalse($input->missing('surname'));
        $this->assertFalse($input->missing(['name', 'surname']));
        $this->assertFalse($input->missing('foo.bar'));
        $this->assertFalse($input->missing(['name', 'foo.baz']));
        $this->assertFalse($input->missing(['name', 'foo']));
        $this->assertFalse($input->missing('foo'));

        $this->assertTrue($input->missing('votes'));
        $this->assertTrue($input->missing(['name', 'votes']));
        $this->assertTrue($input->missing(['votes', 'foo.bar']));
    }

    public function test_when_missing_method()
    {
        $input = new ValidatedInput(['foo' => ['bar' => null]]);

        $name = $age = $city = $foo = $bar = $baz = false;

        $input->whenMissing('name', function () use (&$name) {
            $name = 'Fatih';
        });

        $input->whenMissing('age', function () use (&$age) {
            $age = '';
        });

        $input->whenMissing('city', function () use (&$city) {
            $city = null;
        });

        $input->whenMissing('foo', function ($value) use (&$foo) {
            $foo = $value;
        });

        $input->whenMissing('foo.baz', function () use (&$baz) {
            $baz = true;
        });

        $input->whenMissing('foo.bar', function () use (&$bar) {
            $bar = 'test';
        }, function () use (&$bar) {
            $bar = true;
        });

        $this->assertSame('Fatih', $name);
        $this->assertSame('', $age);
        $this->assertNull($city);
        $this->assertFalse($foo);
        $this->assertTrue($baz);
        $this->assertTrue($bar);
    }

    public function test_keys_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertEquals(['name', 'surname', 'foo'], $input->keys());
    }

    public function test_all_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertEquals(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']], $input->all());
    }

    public function test_input_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertSame('Fatih', $input->input('name'));
        $this->assertSame(null, $input->input('foo.bar'));
        $this->assertSame('test', $input->input('foo.bat', 'test'));
    }

    public function test_str_method()
    {
        $input = new ValidatedInput([
            'int' => 123,
            'int_str' => '456',
            'float' => 123.456,
            'float_str' => '123.456',
            'float_zero' => 0.000,
            'float_str_zero' => '0.000',
            'str' => 'abc',
            'empty_str' => '',
            'null' => null,
        ]);

        $this->assertTrue($input->str('int') instanceof Stringable);
        $this->assertTrue($input->str('int') instanceof Stringable);
        $this->assertTrue($input->str('unknown_key') instanceof Stringable);
        $this->assertSame('123', $input->str('int')->value());
        $this->assertSame('456', $input->str('int_str')->value());
        $this->assertSame('123.456', $input->str('float')->value());
        $this->assertSame('123.456', $input->str('float_str')->value());
        $this->assertSame('0', $input->str('float_zero')->value());
        $this->assertSame('0.000', $input->str('float_str_zero')->value());
        $this->assertSame('', $input->str('empty_str')->value());
        $this->assertSame('', $input->str('null')->value());
        $this->assertSame('', $input->str('unknown_key')->value());
    }

    public function test_string_method()
    {
        $input = new ValidatedInput([
            'int' => 123,
            'int_str' => '456',
            'float' => 123.456,
            'float_str' => '123.456',
            'float_zero' => 0.000,
            'float_str_zero' => '0.000',
            'str' => 'abc',
            'empty_str' => '',
            'null' => null,
        ]);

        $this->assertTrue($input->string('int') instanceof Stringable);
        $this->assertTrue($input->string('int') instanceof Stringable);
        $this->assertTrue($input->string('unknown_key') instanceof Stringable);
        $this->assertSame('123', $input->string('int')->value());
        $this->assertSame('456', $input->string('int_str')->value());
        $this->assertSame('123.456', $input->string('float')->value());
        $this->assertSame('123.456', $input->string('float_str')->value());
        $this->assertSame('0', $input->string('float_zero')->value());
        $this->assertSame('0.000', $input->string('float_str_zero')->value());
        $this->assertSame('', $input->string('empty_str')->value());
        $this->assertSame('', $input->string('null')->value());
        $this->assertSame('', $input->string('unknown_key')->value());
    }

    public function test_boolean_method()
    {
        $input = new ValidatedInput([
            'with_trashed' => 'false',
            'download' => true,
            'checked' => 1,
            'unchecked' => '0',
            'with_on' => 'on',
            'with_yes' => 'yes',
        ]);

        $this->assertTrue($input->boolean('checked'));
        $this->assertTrue($input->boolean('download'));
        $this->assertFalse($input->boolean('unchecked'));
        $this->assertFalse($input->boolean('with_trashed'));
        $this->assertFalse($input->boolean('some_undefined_key'));
        $this->assertTrue($input->boolean('with_on'));
        $this->assertTrue($input->boolean('with_yes'));
    }

    public function test_integer_method()
    {
        $input = new ValidatedInput([
            'int' => '123',
            'raw_int' => 456,
            'zero_padded' => '078',
            'space_padded' => ' 901',
            'nan' => 'nan',
            'mixed' => '1ab',
            'underscore_notation' => '2_000',
            'null' => null,
        ]);

        $this->assertSame(123, $input->integer('int'));
        $this->assertSame(456, $input->integer('raw_int'));
        $this->assertSame(78, $input->integer('zero_padded'));
        $this->assertSame(901, $input->integer('space_padded'));
        $this->assertSame(0, $input->integer('nan'));
        $this->assertSame(1, $input->integer('mixed'));
        $this->assertSame(2, $input->integer('underscore_notation'));
        $this->assertSame(123456, $input->integer('unknown_key', 123456));
        $this->assertSame(0, $input->integer('null'));
        $this->assertSame(0, $input->integer('null', 123456));
    }

    public function test_float_method()
    {
        $input = new ValidatedInput([
            'float' => '1.23',
            'raw_float' => 45.6,
            'decimal_only' => '.6',
            'zero_padded' => '0.78',
            'space_padded' => ' 90.1',
            'nan' => 'nan',
            'mixed' => '1.ab',
            'scientific_notation' => '1e3',
            'null' => null,
        ]);

        $this->assertSame(1.23, $input->float('float'));
        $this->assertSame(45.6, $input->float('raw_float'));
        $this->assertSame(.6, $input->float('decimal_only'));
        $this->assertSame(0.78, $input->float('zero_padded'));
        $this->assertSame(90.1, $input->float('space_padded'));
        $this->assertSame(0.0, $input->float('nan'));
        $this->assertSame(1.0, $input->float('mixed'));
        $this->assertSame(1e3, $input->float('scientific_notation'));
        $this->assertSame(123.456, $input->float('unknown_key', 123.456));
        $this->assertSame(0.0, $input->float('null'));
        $this->assertSame(0.0, $input->float('null', 123.456));
    }

    public function test_date_method()
    {
        $input = new ValidatedInput([
            'as_null' => null,
            'as_invalid' => 'invalid',

            'as_datetime' => '24-01-01 16:30:25',
            'as_format' => '1704126625',
            'as_timezone' => '24-01-01 13:30:25',

            'as_date' => '2024-01-01',
            'as_time' => '16:30:25',
        ]);

        $current = Carbon::create(2024, 1, 1, 16, 30, 25);

        $this->assertNull($input->date('as_null'));
        $this->assertNull($input->date('doesnt_exists'));

        $this->assertEquals($current, $input->date('as_datetime'));
        $this->assertEquals($current->format('Y-m-d H:i:s P'), $input->date('as_format', 'U')->format('Y-m-d H:i:s P'));
        $this->assertEquals($current, $input->date('as_timezone', null, 'America/Santiago'));

        $this->assertTrue($input->date('as_date')->isSameDay($current));
        $this->assertTrue($input->date('as_time')->isSameSecond('16:30:25'));
    }

    public function test_enum_method()
    {
        $input = new ValidatedInput([
            'valid_enum_value' => 'Hello world',
            'invalid_enum_value' => 'invalid',
        ]);

        $this->assertNull($input->enum('doesnt_exists', StringBackedEnum::class));

        $this->assertEquals(StringBackedEnum::HELLO_WORLD, $input->enum('valid_enum_value', StringBackedEnum::class));

        $this->assertNull($input->enum('invalid_enum_value', StringBackedEnum::class));
    }

    public function test_enums_method()
    {
        $input = new ValidatedInput([
            'valid_enum_value' => 'Hello world',
            'invalid_enum_value' => 'invalid',
        ]);

        $this->assertEmpty($input->enums('doesnt_exists', StringBackedEnum::class));

        $this->assertEquals([StringBackedEnum::HELLO_WORLD], $input->enums('valid_enum_value', StringBackedEnum::class));

        $this->assertEmpty($input->enums('invalid_enum_value', StringBackedEnum::class));
    }

    public function test_collect_method()
    {
        $input = new ValidatedInput(['users' => [1, 2, 3]]);

        $this->assertInstanceOf(Collection::class, $input->collect('users'));
        $this->assertTrue($input->collect('developers')->isEmpty());
        $this->assertEquals([1, 2, 3], $input->collect('users')->all());
        $this->assertEquals(['users' => [1, 2, 3]], $input->collect()->all());

        $input = new ValidatedInput(['text-payload']);
        $this->assertEquals(['text-payload'], $input->collect()->all());

        $input = new ValidatedInput(['email' => 'test@example.com']);
        $this->assertEquals(['test@example.com'], $input->collect('email')->all());

        $input = new ValidatedInput([]);
        $this->assertInstanceOf(Collection::class, $input->collect());
        $this->assertTrue($input->collect()->isEmpty());

        $input = new ValidatedInput(['users' => [1, 2, 3], 'roles' => [4, 5, 6], 'foo' => ['bar', 'baz'], 'email' => 'test@example.com']);
        $this->assertInstanceOf(Collection::class, $input->collect(['users']));
        $this->assertTrue($input->collect(['developers'])->isEmpty());
        $this->assertTrue($input->collect(['roles'])->isNotEmpty());
        $this->assertEquals(['roles' => [4, 5, 6]], $input->collect(['roles'])->all());
        $this->assertEquals(['users' => [1, 2, 3], 'email' => 'test@example.com'], $input->collect(['users', 'email'])->all());
        $this->assertEquals(collect(['roles' => [4, 5, 6], 'foo' => ['bar', 'baz']]), $input->collect(['roles', 'foo']));
        $this->assertEquals(['users' => [1, 2, 3], 'roles' => [4, 5, 6], 'foo' => ['bar', 'baz'], 'email' => 'test@example.com'], $input->collect()->all());
    }

    public function test_only_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertEquals(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null]], $input->only('name', 'surname', 'foo.bar'));
        $this->assertEquals(['name' => 'Fatih', 'foo' => ['bar' => null, 'baz' => '']], $input->only('name', 'foo'));
        $this->assertEquals(['foo' => ['baz' => '']], $input->only('foo.baz'));
        $this->assertEquals(['name' => 'Fatih'], $input->only('name'));
    }

    public function test_except_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertEquals(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null]], $input->except('foo.baz'));
        $this->assertEquals(['surname' => 'AYDIN'], $input->except('name', 'foo'));
        $this->assertEquals([], $input->except('name', 'surname', 'foo'));
    }
}
