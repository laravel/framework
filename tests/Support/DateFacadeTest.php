<?php

namespace Illuminate\Tests\Support;

use DateTime;
use Carbon\Factory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Date;

class DateFacadeTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        Date::use(Carbon::class);
        Date::intercept(function ($date) {
            return $date;
        });
    }

    protected static function assertBetweenStartAndNow($start, $actual)
    {
        static::assertThat(
            $actual,
            static::logicalAnd(
                static::greaterThanOrEqual($start),
                static::lessThanOrEqual(Carbon::now()->getTimestamp())
            )
        );
    }

    public function testIntercept()
    {
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(Carbon::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
        Date::intercept(function (Carbon $date) {
            return new DateTime($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
        });
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(DateTime::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
    }

    public function testUse()
    {
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(Carbon::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
        Date::use(DateTime::class);
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(DateTime::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Date class must implement a public static instance(DateTimeInterface $date) method or implements DateTimeInterface.
     */
    public function testUseWrongClass()
    {
        Date::use(Date::class);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Date class must implement a public static instance(DateTimeInterface $date) method or implements DateTimeInterface.
     */
    public function testUseWrongString()
    {
        Date::use('not-a-class');
    }

    public function testCarbonImmutable()
    {
        if (! class_exists(CarbonImmutable::class)) {
            $this->markTestSkipped('Test for Carbon 2 only');
        }

        Date::use(CarbonImmutable::class);
        $this->assertSame(CarbonImmutable::class, get_class(Date::now()));
        Date::use(Carbon::class);
        $this->assertSame(Carbon::class, get_class(Date::now()));
        Date::intercept(function (Carbon $date) {
            return $date->toImmutable();
        });
        $this->assertSame(CarbonImmutable::class, get_class(Date::now()));
        Date::intercept(function ($date) {
            return $date;
        });
        $this->assertSame(Carbon::class, get_class(Date::now()));

        Date::swap(new Factory([
            'locale' => 'fr',
        ]));
        $this->assertSame('fr', Date::now()->locale);
        Date::swap(null);
        $this->assertSame('en', Date::now()->locale);
        include_once __DIR__.'/fixtures/CustomDateClass.php';
        Date::use(\CustomDateClass::class);
        $this->assertInstanceOf(\CustomDateClass::class, Date::now());
        $this->assertInstanceOf(Carbon::class, Date::now()->getOriginal());
        Date::use(Carbon::class);
    }
}
