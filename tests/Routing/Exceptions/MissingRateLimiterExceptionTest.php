<?php

namespace Illuminate\Tests\Routing\Exceptions;

use Exception;
use Illuminate\Routing\Exceptions\MissingRateLimiterException;
use PHPUnit\Framework\TestCase;

class MissingRateLimiterExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException(): void
    {
        $exception = MissingRateLimiterException::forLimiter('uploads');

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testForLimiter(): void
    {
        $exception = MissingRateLimiterException::forLimiter('uploads');

        $this->assertSame('Rate limiter [uploads] is not defined.', $exception->getMessage());
    }

    public function testForLimiterAndUser(): void
    {
        $exception = MissingRateLimiterException::forLimiterAndUser('uploads', 'App\\Models\\User');

        $this->assertSame('Rate limiter [App\\Models\\User::uploads] is not defined.', $exception->getMessage());
    }
}
