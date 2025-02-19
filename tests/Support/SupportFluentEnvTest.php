<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\FluentEnv;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

        // with ";" as separator
        $_ENV['foo'] = 'cheese;wine;42';
        $this->assertSame(['cheese', 'wine', '42'], env()->key('foo')->array(';'));

        // empty values
        $this->assertSame([], env()->key('foo-invalid')->array());
        $this->assertSame([], env()->key('foo-invalid')->collect()->toArray());
    }

    public function testCastToEnum()
    {
        $_ENV['foo-empty'] = '';
        $_ENV['foo-null'] = 'null';

        // UnitEnum
        $_ENV['foo'] = 'Taylor';
        $this->assertSame(UnitEnum::Taylor, env()->key('foo')->enum(UnitEnum::class));
        $this->assertNull(env()->key('foo-empty')->enum(UnitEnum::class));
        $this->assertNull(env()->key('foo-null')->enum(UnitEnum::class));

        // BackedEnum: string
        $_ENV['foo'] = 'cats';
        $this->assertSame(StringEnum::Cats, env()->key('foo')->enum(StringEnum::class));
        $this->assertNull(env()->key('foo-empty')->enum(StringEnum::class));
        $this->assertNull(env()->key('foo-null')->enum(StringEnum::class));

        // BackedEnum: integer
        $_ENV['foo'] = '2';
        $this->assertSame(IntegerEnum::Two, env()->key('foo')->enum(IntegerEnum::class));
        $this->assertNull(env()->key('foo-empty')->enum(IntegerEnum::class));
        $this->assertNull(env()->key('foo-null')->enum(IntegerEnum::class));
    }

    public function testFailedCastToUnitEnum()
    {
        $_ENV['NUMBER'] = 'not-a-number';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable [NUMBER] is not a valid value for enum Illuminate\Tests\Support\UnitEnum.');

        env()->key('NUMBER')->enum(UnitEnum::class);
    }

    public function testFailedCastToBackedEnum()
    {
        $_ENV['PET'] = 'not-a-pet';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable [PET] is not a valid value for enum Illuminate\Tests\Support\StringEnum.');

        env()->key('PET')->enum(StringEnum::class);
    }

    public function testEnumDoesntExists()
    {
        $_ENV['foo'] = 'bar';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Environment variable [foo] error: Enum InexistantEnum doesn't exist.");

        env()->key('foo')->enum('InexistantEnum');
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
        $_ENV['FOO'] = 'We must ship';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable [FOO] is invalid: The environment variable field must be at least 20 characters');

        env()->key('FOO')->rules(['required', 'min:20'])->get();
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
