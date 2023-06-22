<?php

namespace Illuminate\Tests\Support;

use BadMethodCallException;
use Illuminate\Support\Traits\Macroable;
use PHPUnit\Framework\TestCase;

class SupportMacroableTest extends TestCase
{
    private $macroable;

    protected function setUp(): void
    {
        $this->macroable = $this->createObjectForTrait();
    }

    private function createObjectForTrait()
    {
        return new EmptyMacroable;
    }

    public function testRegisterMacro()
    {
        $macroable = $this->macroable;
        $macroable::macro(__CLASS__, function () {
            return 'Taylor';
        });
        $this->assertSame('Taylor', $macroable::{__CLASS__}());
    }

    public function testHasMacro()
    {
        $macroable = $this->macroable;
        $macroable::macro('foo', function () {
            return 'Taylor';
        });
        $this->assertTrue($macroable::hasMacro('foo'));
        $this->assertFalse($macroable::hasMacro('bar'));
    }

    public function testRegisterMacroAndCallWithoutStatic()
    {
        $macroable = $this->macroable;
        $macroable::macro(__CLASS__, function () {
            return 'Taylor';
        });
        $this->assertSame('Taylor', $macroable->{__CLASS__}());
    }

    public function testWhenCallingMacroClosureIsBoundToObject()
    {
        TestMacroable::macro('tryInstance', function () {
            return $this->protectedVariable;
        });
        TestMacroable::macro('tryStatic', function () {
            return static::getProtectedStatic();
        });
        $instance = new TestMacroable;

        $result = $instance->tryInstance();
        $this->assertSame('instance', $result);

        $result = TestMacroable::tryStatic();
        $this->assertSame('static', $result);
    }

    public function testClassBasedMacrosPassedAsString()
    {
        TestMacroable::mixin(TestClassMixin::class);
        $instance = new TestMacroable;
        $this->assertSame('instance-Adam', $instance->methodOne('Adam'));
    }

    public function testClassBasedMacrosPassedAsObject()
    {
        TestMacroable::mixin(new TestClassMixin);
        $instance = new TestMacroable('dynamic');
        $this->assertSame('instance-Adam', $instance->methodOne('Adam'));
        $this->assertSame('dynamic', $instance->methodZero());
    }

    public function testClassBasedMacrosStaticCalls()
    {
        TestMacroable::mixin(TestClassMixin::class);
        $this->assertSame('static', TestMacroable::tryStatic());
        $this->assertSame('foo', TestMacroable::methodThree());
    }

    public function testClassBasedMacrosNoReplace()
    {
        TestMacroable::macro('methodThree', function () {
            return 'bar';
        });
        TestMacroable::mixin(new TestClassMixin, false);
        $instance = new TestMacroable;
        $this->assertSame('bar', $instance->methodThree());

        TestMacroable::mixin(new TestClassMixin);
        $this->assertSame('foo', $instance->methodThree());
    }

    public function testTraitBasedMacros()
    {
        TestMacroable::mixin(TestTraitMixin::class);
        $instance = new TestMacroable('dynamic');
        $this->assertSame('instance-Adam', $instance->methodOne(value: 'Adam'));
        $this->assertSame('dynamic', $instance->methodZero());
    }

    public function testTraitBasedMacrosNoReplace()
    {
        TestMacroable::macro('methodThree', function () {
            return 'bar';
        });
        TestMacroable::mixin(TestTraitMixin::class, false);
        $instance = new TestMacroable;
        $this->assertSame('bar', $instance->methodThree());

        TestMacroable::mixin(TestTraitMixin::class);
        $this->assertSame('foo', $instance->methodThree());
    }

    public function testTraitBasedMacrosStaticCalls()
    {
        TestMacroable::mixin(TestTraitMixin::class);
        $this->assertSame('static', TestMacroable::tryStatic());
        $this->assertSame('foo', TestMacroable::methodThree());
    }

    public function testTraitWithTraitBasedMacros()
    {
        TestMacroable::mixin(TestTraitMixinWithAntherTrait::class);
        $instance = new TestMacroable('dynamic');
        $this->assertSame('instance-Adam', $instance->methodOne(value: 'Adam'));
        $this->assertSame('instance-Adam', $instance->methodFour(value: 'Adam'));
        $this->assertSame('dynamic', $instance->methodZero());
    }

    public function testTraitWithTraitBasedMacrosNoReplace()
    {
        TestMacroable::macro('methodThree', function () {
            return 'bar';
        });
        TestMacroable::macro('methodFive', function () {
            return 'bar';
        });

        TestMacroable::mixin(TestTraitMixinWithAntherTrait::class, false);
        $instance = new TestMacroable;
        $this->assertSame('bar', $instance->methodThree());
        $this->assertSame('bar', $instance->methodFive());

        TestMacroable::mixin(TestTraitMixinWithAntherTrait::class);
        $this->assertSame('foo', $instance->methodThree());
        $this->assertSame('foo', $instance->methodFive());
    }

    public function testTraitWithTraitBasedMacrosStaticCalls()
    {
        TestMacroable::mixin(TestTraitMixin::class);
        $this->assertSame('static', TestMacroable::tryStatic());
        $this->assertSame('foo', TestMacroable::methodThree());
    }

    public function testFlushMacros()
    {
        TestMacroable::macro('flushMethod', function () {
            return 'flushMethod';
        });

        $instance = new TestMacroable;

        $this->assertSame('flushMethod', $instance->flushMethod());

        TestMacroable::flushMacros();

        $this->expectException(BadMethodCallException::class);

        $instance->flushMethod();
    }

    public function testFlushMacrosStatic()
    {
        TestMacroable::macro('flushMethod', function () {
            return 'flushMethod';
        });

        $instance = new TestMacroable;

        $this->assertSame('flushMethod', $instance::flushMethod());

        TestMacroable::flushMacros();

        $this->expectException(BadMethodCallException::class);

        $instance::flushMethod();
    }
}

class EmptyMacroable
{
    use Macroable;
}

class TestMacroable
{
    use Macroable;

    protected $protectedVariable = 'instance';

    public function __construct(
        protected $dynamicVariable = null,
    ) {
    }

    protected static function getProtectedStatic()
    {
        return 'static';
    }
}

class TestClassMixin
{
    public function methodZero()
    {
        return function () {
            return $this->dynamicVariable;
        };
    }

    public function methodOne()
    {
        return function ($value) {
            return $this->methodTwo($value);
        };
    }

    protected function methodTwo()
    {
        return function ($value) {
            return $this->protectedVariable.'-'.$value;
        };
    }

    protected function methodThree()
    {
        return function () {
            return 'foo';
        };
    }

    public static function tryStatic()
    {
        return function () {
            return static::getProtectedStatic();
        };
    }
}

trait TestTraitMixin
{
    public function methodZero()
    {
        return self::this()->dynamicVariable;
    }

    public function methodOne($value)
    {
        return self::this()->methodTwo($value);
    }

    protected function methodTwo($value)
    {
        return self::this()->protectedVariable.'-'.$value;
    }

    protected function methodThree()
    {
        return 'foo';
    }

    public static function tryStatic()
    {
        return static::getProtectedStatic();
    }
}

trait TestTraitMixinWithAntherTrait
{
    use TestTraitMixin;

    public function methodFour($value)
    {
        return self::this()->methodTwo($value);
    }

    protected function methodFive()
    {
        return self::this()->methodThree();
    }
}
