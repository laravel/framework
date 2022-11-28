<?php

namespace Illuminate\Tests\Support;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Clock;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class ClockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('clock', new \Illuminate\Support\Clock());

        Facade::setFacadeApplication($container);
    }

    public function testDateInstance()
    {
        $this->assertInstanceOf(DateTimeImmutable::class, Clock::now());
        $this->assertInstanceOf(DateTimeImmutable::class, Clock::withTimezone(new DateTimeZone('Europe/London')));
        $this->assertInstanceOf(DateTimeImmutable::class, Clock::createFromFormat('Y-m-d', '2022-11-28'));
    }

    public function testNowReturnsCorrectDateTime()
    {
        $clockNow = Clock::now();
        $now = new DateTimeImmutable();
        $this->assertTrue($clockNow->getTimestamp() === $now->getTimestamp());
    }

    public function testWithTimezoneReturnsCorrectDateTime()
    {
        $clockNow = Clock::withTimezone(new DateTimeZone('Europe/London'));
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/London'));
        $this->assertTrue($clockNow->getTimestamp() === $now->getTimestamp());
    }

    public function testCreateFromFormatReturnsCorrectDateTime()
    {
        $clockNow = Clock::createFromFormat('Y-m-d H:i:s', '2022-11-28 12:00:00');
        $now = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2022-11-28 12:00:00');
        $this->assertTrue($clockNow->getTimestamp() === $now->getTimestamp());
    }

    public function testCreateFromFormatShouldReturnFalseWithWrongFormat()
    {
        $this->assertFalse(Clock::createFromFormat('Y-m-d', '2022-11-28 12:00:00'));
    }
}
