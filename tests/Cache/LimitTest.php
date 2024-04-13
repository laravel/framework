<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RateLimiting\GlobalLimit;
use Illuminate\Cache\RateLimiting\Limit;
use PHPUnit\Framework\TestCase;

class LimitTest extends TestCase
{
    public function testConstructors()
    {
        $limit = new Limit('', 3, 1);
        $this->assertSame(1, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = Limit::perSecond(3);
        $this->assertSame(1, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = Limit::perSecond(3, 5);
        $this->assertSame(5, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = Limit::perMinute(3);
        $this->assertSame(60, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = Limit::perMinute(3, 4);
        $this->assertSame(240, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = Limit::perMinutes(2, 3);
        $this->assertSame(120, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = Limit::perHour(3);
        $this->assertSame(3600, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = Limit::perHour(3, 2);
        $this->assertSame(7200, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = Limit::perDay(3);
        $this->assertSame(86400, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = Limit::perDay(3, 5);
        $this->assertSame(432000, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);

        $limit = new GlobalLimit(3);
        $this->assertSame(60, $limit->decaySeconds);
        $this->assertSame(3, $limit->maxAttempts);
    }
}
