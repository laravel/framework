<?php

namespace Illuminate\Tests\Routing\Exceptions;

use Exception;
use Illuminate\Routing\Exceptions\MissingRateLimiterException;
use PHPUnit\Framework\TestCase;

class MissingRateLimiterExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException()
    {
        $exception = MissingRateLimiterException::forLimiter('uploads');

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testForLimiter()
    {
        $exception = MissingRateLimiterException::forLimiter('uploads');

        $this->assertSame('Rate limiter [uploads] is not defined.', $exception->getMessage());
    }

    public function testForLimiterAndUser()
    {
        $exception = MissingRateLimiterException::forLimiterAndUser('uploads', 'App\\Models\\User');

        $this->assertSame('Rate limiter [App\\Models\\User::uploads] is not defined.', $exception->getMessage());
    }
}
