<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\CacheTTL;
use PHPUnit\Framework\TestCase;

class CacheTTLTest extends TestCase
{
    public function testReturnsTtlForSeconds()
    {
        $this->assertSame(CacheTTL::SECOND->value, CacheTTL::second());
        $this->assertSame(CacheTTL::SECOND->value * 30, CacheTTL::seconds(30));
    }

    public function testReturnsTtlForMinutes()
    {
        $this->assertSame(CacheTTL::MINUTE->value, CacheTTL::minute());
        $this->assertSame(CacheTTL::MINUTE->value * 5, CacheTTL::minutes(5));
    }

    public function testReturnsTtlForHours()
    {
        $this->assertSame(CacheTTL::HOUR->value, CacheTTL::hour());
        $this->assertSame(CacheTTL::HOUR->value * 2, CacheTTL::hours(2));
    }

    public function testReturnsTtlForDays()
    {
        $this->assertSame(CacheTTL::DAY->value, CacheTTL::day());
        $this->assertSame(CacheTTL::DAY->value * 3, CacheTTL::days(3));
    }

    public function testReturnsTtlForWeeks()
    {
        $this->assertSame(CacheTTL::DAY->value * 7, CacheTTL::week());
        $this->assertSame(CacheTTL::DAY->value * 7 * 2, CacheTTL::weeks(2));
    }

    public function testReturnsTtlForMonths()
    {
        $this->assertSame(CacheTTL::DAY->value * 30, CacheTTL::month());
        $this->assertSame(CacheTTL::DAY->value * 30 * 2, CacheTTL::months(2));
    }

    public function testReturnsTtlForYears()
    {
        $this->assertSame(CacheTTL::DAY->value * 365, CacheTTL::year());
        $this->assertSame(CacheTTL::DAY->value * 365 * 2, CacheTTL::years(2));
    }
}
