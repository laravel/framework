<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\FluentEnv;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class SupportFluentEnvTest extends TestCase
{
    public function testBasics()
    {
        $_ENV['foo'] = 'var';

        // From env()
        $this->assertSame('var', env()->key('foo')->get());
        // From instance
        $this->assertSame('var', (new FluentEnv('foo'))->get());
    }

    public function testDefault()
    {
        unset($_ENV['foo']);

        // no default
        $this->assertNull(env()->key('foo')->get());
        // default
        $this->assertSame('default', env()->key('foo', 'default')->get());
        $this->assertSame('default', env()->key('foo')->default('default')->get());
        // default as fucntion
        $this->assertSame('default', env()->key('foo')->default(fn () => 'default')->get());
    }

    public function testWithFallbackKeys()
    {
        $_ENV['foo'] = 'var';

        $this->assertSame('var', env()->keys('wrong-key', 'foo')->get());
        $this->assertSame('default', env()->keys('wrong-key-1', 'wrong-key-2')->default('default')->get());
        $this->assertNull(env()->keys('wrong-key-1', 'wrong-key-2')->get());
    }

    public function testCasting()
    {
        unset($_ENV['foo']);

        // cast
        $this->assertSame('string', env()->key('foo')->default('string')->string());
        $this->assertSame(123, env()->key('foo')->default('123')->integer());
        $this->assertSame(1.23, env()->key('foo')->default('1.23')->float());
        $this->assertSame(false, env()->key('foo')->boolean());

        // null
        $this->assertSame('', env()->key('foo')->string());
        $this->assertSame(0, env()->key('foo')->integer());
        $this->assertSame(0.0, env()->key('foo')->float());
        $this->assertSame(false, env()->key('foo')->boolean());

        // Stringable
        $this->assertSame('STRING', env()->default('string')->stringable()->upper()->value());
    }

    public function testCastToArray()
    {
        // array
        $_ENV['foo'] = 'cheese,wine,42';
        $this->assertSame(['cheese', 'wine', '42'], env()->key('foo')->array());

        // collection with ";" as separator
        $_ENV['foo'] = 'cheese;wine;42';
        $this->assertSame(['cheese', 'wine', '42'], env()->key('foo')->collect(';')->toArray());

        // empty values
        $this->assertSame([], env()->key('foo-invalid')->array());
        $this->assertSame([], env()->key('foo-invalid')->collect()->toArray());

        // cast to integer
        $_ENV['foo'] = '4|8|15|16|23|42';
        $this->assertSame([4, 8, 15, 16, 23, 42], env()->key('foo')->array(separator: '|', cast: 'integer'));
    }

    public function testCastToEnum()
    {
        $_ENV['foo-invalid'] = 'invalid';

        // UnitEnum
        $_ENV['foo'] = 'Taylor';
        $this->assertSame(UnitEnum::Taylor, env()->key('foo')->enum(UnitEnum::class));
        $this->assertNull(env()->key('foo-invalid')->enum(UnitEnum::class));

        // BackedEnum: string
        $_ENV['foo'] = 'cats';
        $this->assertSame(StringEnum::Cats, env()->key('foo')->enum(StringEnum::class));
        $this->assertNull(env()->key('foo-invalid')->enum(StringEnum::class));

        // BackedEnum: integer
        $_ENV['foo'] = '2';
        $this->assertSame(IntegerEnum::Two, env()->key('foo')->enum(IntegerEnum::class));
        $this->assertNull(env()->key('foo-invalid')->enum(IntegerEnum::class));
    }

    public function testValidationRules()
    {
        $_ENV['foo'] = '2011-06-09';

        // String
        $this->assertSame('2011-06-09', env()->key('foo')->rules('required')->get());
        // Array
        $this->assertSame('2011-06-09', env()->key('foo')->rules(['required'])->get());
        // Stringable
        $this->assertSame('2011-06-09', env()->key('foo')->rules(Rule::date()->beforeToday())->get());
    }

    public function testValidationRulesException()
    {
        $_ENV['foo'] = 'We must ship';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('validation.min.string');

        env()->key('foo')->rules(['required', 'min:20'])->get();
    }

    public function testRulesExceptionForDefaultValue()
    {
        unset($_ENV['foo']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('validation.ends_with');

        env()->key('foo')->default('We must ship')->rules('ends_with:eat')->get();
    }
}

enum UnitEnum
{
    case Taylor;
    case Swift;
}

enum StringEnum: string
{
    case Cats = 'cats';
    case Dogs = 'dogs';
}

enum IntegerEnum: int
{
    case One = 1;
    case Two = 2;
}
