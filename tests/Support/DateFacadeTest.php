<?php

namespace Illuminate\Tests\Support;

use DateTime;
use Carbon\Factory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\DateFactory;
use Illuminate\Support\Facades\Date;

class DateFacadeTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        DateFactory::use(Carbon::class);
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

    public function testUseClosure()
    {
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(Carbon::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
        DateFactory::use(function (Carbon $date) {
            return new DateTime($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
        });
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(DateTime::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
    }

    public function testUseClassName()
    {
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(Carbon::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
        DateFactory::use(DateTime::class);
        $start = Carbon::now()->getTimestamp();
        $this->assertSame(DateTime::class, get_class(Date::now()));
        $this->assertBetweenStartAndNow($start, Date::now()->getTimestamp());
    }

    public function testCarbonImmutable()
    {
        if (! class_exists(CarbonImmutable::class)) {
            $this->markTestSkipped('Test for Carbon 2 only');
        }

        DateFactory::use(CarbonImmutable::class);
        $this->assertSame(CarbonImmutable::class, get_class(Date::now()));
        DateFactory::use(Carbon::class);
        $this->assertSame(Carbon::class, get_class(Date::now()));
        DateFactory::use(function (Carbon $date) {
            return $date->toImmutable();
        });
        $this->assertSame(CarbonImmutable::class, get_class(Date::now()));
        DateFactory::use(function ($date) {
            return $date;
        });
        $this->assertSame(Carbon::class, get_class(Date::now()));

        DateFactory::use(new Factory([
            'locale' => 'fr',
        ]));
        $this->assertSame('fr', Date::now()->locale);
        DateFactory::use(Carbon::class);
        $this->assertSame('en', Date::now()->locale);
        include_once __DIR__.'/fixtures/CustomDateClass.php';
        DateFactory::use(\CustomDateClass::class);
        $this->assertInstanceOf(\CustomDateClass::class, Date::now());
        $this->assertInstanceOf(Carbon::class, Date::now()->getOriginal());
        DateFactory::use(Carbon::class);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUseInvalidHandler()
    {
        DateFactory::use(42);
    }
}
