<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ContainerDecorateTest extends TestCase
{
    public function testDecorateExistingBinding()
    {
        $container = new Container();
        $container->singleton(IClock::class, Clock::class);
        $container->decorate(IClock::class, CachedClock::class);

        $clock = $container->get(IClock::class);
        $timestamp = $clock->utcNow();

        $this->assertInstanceOf(CachedClock::class, $clock);
        $this->assertEquals($timestamp, $clock->utcNow());
    }

    public function testChainingDecorators()
    {
        $container = new Container();
        $container->singleton(IClock::class, Clock::class);
        $container->decorate(IClock::class, CachedClock::class);
        $container->decorate(IClock::class, PrettyPrintClock::class);
        $container->singleton(IClockFormatter::class, ClockFormatter::class);

        $clock = $container->get(IClock::class);
        $timestamp = $clock->utcNow();

        $this->assertInstanceOf(PrettyPrintClock::class, $clock);
        $this->assertStringStartsWith('[', $timestamp);
        $this->assertStringEndsWith(']', $timestamp);
        $this->assertEquals($timestamp, $clock->utcNow());
    }

    public function testThrowExceptionWhenBindingDoesNotExist()
    {
        $container = new Container();
        $this->expectException(InvalidArgumentException::class);
        $container->decorate(IClock::class, Clock::class);
    }

    public function testDecoratorHasTheSameLifetimeAsDecoratedOne()
    {
        $container = new Container();
        $container->bind(IClock::class, Clock::class);
        $container->decorate(IClock::class, CachedClock::class);

        $clock1 = $container->get(IClock::class);
        $clock2 = $container->get(IClock::class);

        $this->assertNotSame($clock1, $clock2);
        $this->assertInstanceOf(CachedClock::class, $clock1);
        $this->assertInstanceOf(CachedClock::class, $clock2);
    }

    public function testReboundSingleton()
    {
        $container = new Container();
        $container->singleton(IClock::class, Clock::class);

        $clock1 = $container->get(IClock::class);
        $container->decorate(IClock::class, CachedClock::class);
        $clock2 = $container->get(IClock::class);

        $this->assertNotSame($clock1, $clock2);
        $this->assertInstanceOf(Clock::class, $clock1);
        $this->assertInstanceOf(CachedClock::class, $clock2);
    }

    public function testReboundDependency()
    {
        $container = new Container();
        $container->singleton(IClock::class, Clock::class);
        $container->bind(ClockHolder::class);

        $holder1 = $container->get(ClockHolder::class);
        $container->decorate(IClock::class, CachedClock::class);
        $holder2 = $container->get(ClockHolder::class);

        $this->assertNotSame($holder1, $holder2);
        $this->assertInstanceOf(Clock::class, $holder1->clock);
        $this->assertInstanceOf(CachedClock::class, $holder2->clock);
    }

    public function testRecursive()
    {
        $container = new Container();
        $container->singleton(IClockFormatter::class, ClockFormatter::class);
        $container->singleton(IClock::class, Clock::class);
        $container->decorate(IClock::class, CachedClock::class);
        $container->decorate(IClock::class, PrettyPrintClock::class);

        $clock = $container->make(CachedClock::class);

        $this->assertInstanceOf(CachedClock::class, $clock);
        $this->assertInstanceOf(PrettyPrintClock::class, $clock->origin);
        $this->assertInstanceOf(CachedClock::class, $clock->origin->origin);
        $this->assertInstanceOf(Clock::class, $clock->origin->origin->origin);
    }
}

interface IClock
{
    public function utcNow(): string;
}

class Clock implements IClock
{
    public function utcNow(): string
    {
        return (string)time();
    }
}

class CachedClock implements IClock
{
    public IClock $origin;
    private ?string $timestamp = null;

    public function __construct(IClock $origin)
    {
        $this->origin = $origin;
    }

    public function utcNow(): string
    {
        if (is_null($this->timestamp)) {
            $this->timestamp = $this->origin->utcNow();
        }
        return $this->timestamp;
    }
}

class PrettyPrintClock implements IClock
{
    public IClock $origin;
    private IClockFormatter $formatter;

    public function __construct(IClock $origin, IClockFormatter $fomatter)
    {
        $this->origin = $origin;
        $this->formatter = $fomatter;
    }

    public function utcNow(): string
    {
        return $this->formatter->format($this->origin);
    }
}

interface IClockFormatter
{
    public function format(IClock $clock): string;
}

class ClockFormatter implements IClockFormatter
{
    public function format(IClock $clock): string
    {
        $timestamp = $clock->utcNow();

        return sprintf('[%s]', $timestamp);
    }
}

class ClockHolder
{
    public IClock $clock;

    public function __construct(IClock $clock)
    {
        $this->clock = $clock;
    }
}
