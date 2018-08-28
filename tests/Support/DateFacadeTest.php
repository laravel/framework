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
        Date::swap(Carbon::class);
        Date::swap(function ($date) {
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

    public function testSwapClosure()
    {
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(Carbon::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
        Date::swap(function (Carbon $date) {
            return new DateTime($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
        });
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(DateTime::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
    }

    public function testSwapClassName()
    {
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(Carbon::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
        Date::swap(DateTime::class);
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(DateTime::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
    }

    public function testCarbonImmutable()
    {
        if (! class_exists(CarbonImmutable::class)) {
            $this->markTestSkipped('Test for Carbon 2 only');
        }

        Date::swap(CarbonImmutable::class);
        $this->assertSame(CarbonImmutable::class, get_class(Date::now()));
        Date::swap(Carbon::class);
        $this->assertSame(Carbon::class, get_class(Date::now()));
        Date::swap(function (Carbon $date) {
            return $date->toImmutable();
        });
        $this->assertSame(CarbonImmutable::class, get_class(Date::now()));
        Date::swap(function ($date) {
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
        Date::swap(\CustomDateClass::class);
        $this->assertInstanceOf(\CustomDateClass::class, Date::now());
        $this->assertInstanceOf(Carbon::class, Date::now()->getOriginal());
        Date::swap(Carbon::class);
    }
}
