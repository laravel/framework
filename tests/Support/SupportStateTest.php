<?php

namespace Illuminate\Tests\Support;

use BadMethodCallException;
use Illuminate\Support\State;
use PHPUnit\Framework\TestCase;

class SupportStateTest extends TestCase
{
    public function testCreatesEnumerate()
    {
        $this->assertInstanceOf(State::class, State::from(['foo', 'bar']));
    }

    public function testCreatesEnumerateWithInitialValue()
    {
        $enum = State::from(['foo', 'bar'], 'foo');

        $this->assertEquals('foo', $enum->current());
    }

    public function testSetsStates()
    {
        $enum = State::from($states = ['foo', 'bar', 'quz']);

        $this->assertEquals($states, $enum->states());
    }

    public function testStateExists()
    {
        $enum = State::from(['foo', 'bar', 'quz']);

        $this->assertTrue($enum->has('foo'));
        $this->assertFalse($enum->has('doesnt_exists'));

        $enum = State::from($states = [
            'foo' => 10,
            'qux' => null,
        ]);

        $this->assertTrue($enum->has('foo'));
        $this->assertTrue($enum->has('qux'));
    }

    public function testIs()
    {
        $enum = State::from(['foo', 'bar', 'quz']);

        $this->assertFalse($enum->is('foo'));
        $this->assertFalse($enum->is('bar'));

        $enum->foo();

        $this->assertTrue($enum->is('foo'));
        $this->assertFalse($enum->is('bar'));
    }

    public function testCurrentState()
    {
        $enum = State::from(['foo', 'bar', 'quz']);

        $this->assertNull($enum->current());

        $enum->foo();

        $this->assertEquals('foo', $enum->current());
    }

    public function testDynamicallySetsStatesAndReturnsCurrentValue()
    {
        $enum = State::from($states = ['foo', 'bar', 'quz']);

        foreach ($states as $state) {
            $this->assertInstanceOf(State::class, $enum->{$state}());
            $this->assertEquals($state, $enum->value());
        }
    }

    public function testReturnsMapValue()
    {
        $enum = State::from($states = [
            'foo' => 10,
            'bar' => function () {
                return true;
            },
            'quz' => [],
            'qux' => null,
        ]);

        $this->assertIsInt($enum->foo()->value());
        $this->assertIsCallable($enum->bar()->value());
        $this->assertIsArray($enum->quz()->value());
        $this->assertNull($enum->qux()->value());
    }

    public function testExceptionWhenStateInvalid()
    {
        $this->expectException(BadMethodCallException::class);

        $enum = State::from($states = ['foo', 'bar', 'quz']);

        $enum->invalid();
    }

    public function testToString()
    {
        $enum = State::from($states = ['foo', 'bar', 'quz']);

        $this->assertEquals('', (string) $enum);

        $enum->foo();

        $this->assertEquals('foo', (string) $enum);

        $enum = State::from($states = [
            'foo' => 10,
            'bar' => function () {
                return true;
            },
            'quz' => [],
            'qux' => null,
        ]);

        $enum->bar();

        $this->assertEquals('bar', (string) $enum);
    }

    public function testUsesInitialState()
    {
        $class = new class extends State {
            protected $current = 'foo';
            protected $states = ['foo', 'bar'];
        };

        $this->assertEquals('foo', $class->current());
    }
}
