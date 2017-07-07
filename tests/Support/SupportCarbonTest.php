<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class SupportCarbonTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2017, 6, 27, 13, 14, 15));
    }

    public function tearDown()
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testCarbonIsMacroableWhenNotCalledStatically()
    {
        Carbon::macro('diffInDecades', function (Carbon $dt = null, $abs = true) {
            return (int) ($this->diffInYears($dt, $abs) / 10);
        });

        $this->assertSame(2, Carbon::now()->diffInDecades(Carbon::now()->addYears(25)));
    }

    public function testCarbonIsMacroableWhenCalledStatically()
    {
        Carbon::macro('twoDaysAgoAtNoon', function () {
            return Carbon::now()->subDays(2)->setTime(12, 0, 0);
        });

        $this->assertSame('2017-06-25 12:00:00', Carbon::twoDaysAgoAtNoon()->toDateTimeString());
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method nonExistingStaticMacro does not exist.
     */
    public function testCarbonRaisesExceptionWhenStaticMacroIsNotFound()
    {
        Carbon::nonExistingStaticMacro();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method nonExistingMacro does not exist.
     */
    public function testCarbonRaisesExceptionWhenMacroIsNotFound()
    {
        Carbon::now()->nonExistingMacro();
    }
}
