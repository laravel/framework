<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\FluentEnv;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SupportFluentEnvTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['FOO'], $_ENV['FOO_EMPTY'], $_ENV['FOO_NULL']);
    }

    public function testBasics()
    {
        $_ENV['FOO'] = 'var';

        // From env()
        $this->assertSame('var', env()->key('FOO')->get());
        // From instance
        $this->assertSame('var', (new FluentEnv('FOO'))->get());
    }

    public function testDefault()
    {
        unset($_ENV['FOO']);

        // no default
        $this->assertNull(env()->key('FOO')->get());
        // default
        $this->assertSame('default', env()->key('FOO', 'default')->get());
        $this->assertSame('default', env()->key('FOO')->default('default')->get());
        // default as fucntion
        $this->assertSame('default', env()->key('FOO')->default(fn () => 'default')->get());
    }

    public function testWithFallbackKeys()
    {
        $_ENV['FOO'] = 'var';

        $this->assertSame('var', env()->keys('wrong-key', 'FOO')->get());
        $this->assertSame('default', env()->keys('wrong-key-1', 'wrong-key-2')->default('default')->get());
        $this->assertNull(env()->keys('wrong-key-1', 'wrong-key-2')->get());
    }

    public function testCasting()
    {
        unset($_ENV['FOO']);

        // cast
        $this->assertSame('string', env()->key('FOO')->default('string')->string());
        $this->assertSame(123, env()->key('FOO')->default('123')->integer());
        $this->assertSame(1.23, env()->key('FOO')->default('1.23')->float());
        $this->assertSame(false, env()->key('FOO')->boolean());

        // null
        $this->assertSame('', env()->key('FOO')->string());
        $this->assertSame(0, env()->key('FOO')->integer());
        $this->assertSame(0.0, env()->key('FOO')->float());
        $this->assertSame(false, env()->key('FOO')->boolean());

        // Stringable
        $this->assertSame('STRING', env()->default('string')->stringable()->upper()->value());
    }

    public function testCastToArray()
    {
        // array
        $_ENV['FOO'] = 'cheese,wine,42';
        $this->assertSame(['cheese', 'wine', '42'], env()->key('FOO')->array());

        // with ";" as separator
        $_ENV['FOO'] = 'cheese;wine;42';
        $this->assertSame(['cheese', 'wine', '42'], env()->key('FOO')->array(';'));

        // empty values
        $this->assertSame([], env()->key('FOO-invalid')->array());
        $this->assertSame([], env()->key('FOO-invalid')->collect()->toArray());
    }

    public function testCastToEnum()
    {
        $_ENV['FOO_EMPTY'] = '';
        $_ENV['FOO_NULL'] = 'null';

        // UnitEnum
        $_ENV['FOO'] = 'Taylor';
        $this->assertSame(UnitEnum::Taylor, env()->key('FOO')->enum(UnitEnum::class));
        $this->assertNull(env()->key('FOO_EMPTY')->enum(UnitEnum::class));
        $this->assertNull(env()->key('FOO_NULL')->enum(UnitEnum::class));

        // BackedEnum: string
        $_ENV['FOO'] = 'cats';
        $this->assertSame(StringEnum::Cats, env()->key('FOO')->enum(StringEnum::class));
        $this->assertNull(env()->key('FOO_EMPTY')->enum(StringEnum::class));
        $this->assertNull(env()->key('FOO_NULL')->enum(StringEnum::class));

        // BackedEnum: integer
        $_ENV['FOO'] = '2';
        $this->assertSame(IntegerEnum::Two, env()->key('FOO')->enum(IntegerEnum::class));
        $this->assertNull(env()->key('FOO_EMPTY')->enum(IntegerEnum::class));
        $this->assertNull(env()->key('FOO_NULL')->enum(IntegerEnum::class));
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
        $_ENV['FOO'] = 'bar';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Environment variable [FOO] error: Enum InexistantEnum doesn't exist.");

        env()->key('FOO')->enum('InexistantEnum');
    }

    public function testValidationRules()
    {
        $_ENV['FOO'] = '2011-06-09';

        // String
        $this->assertSame('2011-06-09', env()->key('FOO')->rules('required')->get());
        // Array
        $this->assertSame('2011-06-09', env()->key('FOO')->rules(['required'])->get());
        // Stringable
        $this->assertSame('2011-06-09', env()->key('FOO')->rules(Rule::date()->beforeToday())->get());
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
