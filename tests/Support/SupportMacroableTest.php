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

    public function testRegisterMacroInChildClass()
    {
        $macroable = $this->macroable;
        $childMacroable = new ChildMacroable;

        $macroable::macro('originalMethod', function () {
            return 'parent - originalMethod';
        });

        $childMacroable::macro('originalMethod', function () {
            return 'child - originalMethod';
        });

        $childMacroable::macro('newMethod', function () {
            return 'child - newMethod';
        });

        $this->assertSame('parent - originalMethod', $macroable->originalMethod());
        $this->assertSame('child - originalMethod', $childMacroable->originalMethod());
        $this->assertSame('child - newMethod', $childMacroable->newMethod());

        $this->expectException(BadMethodCallException::class);
        $macroable->newMethod();
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

    public function testClassBasedMacros()
    {
        TestMacroable::mixin(new TestMixin);
        $instance = new TestMacroable;
        $this->assertSame('instance-Adam', $instance->methodOne('Adam'));
    }

    public function testClassBasedMacrosNoReplace()
    {
        TestMacroable::macro('methodThree', function () {
            return 'bar';
        });
        TestMacroable::mixin(new TestMixin, false);
        $instance = new TestMacroable;
        $this->assertSame('bar', $instance->methodThree());

        TestMacroable::mixin(new TestMixin);
        $this->assertSame('foo', $instance->methodThree());
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

class ChildMacroable extends EmptyMacroable
{
}

class TestMacroable
{
    use Macroable;

    protected $protectedVariable = 'instance';

    protected static function getProtectedStatic()
    {
        return 'static';
    }
}

class TestMixin
{
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
}
