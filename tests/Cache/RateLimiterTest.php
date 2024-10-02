<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository as Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class RateLimiterTest extends TestCase
{
    public static function registerNamedRateLimiterDataProvider(): array
    {
        return [
            'uses BackedEnum' => [BackedEnumNamedRateLimiter::API, 'api'],
            'uses UnitEnum' => [UnitEnumNamedRateLimiter::THIRD_PARTY, 'THIRD_PARTY'],
            'uses normal string' => ['yolo', 'yolo'],
            'uses int' => [100, '100'],
        ];
    }

    #[DataProvider('registerNamedRateLimiterDataProvider')]
    public function testRegisterNamedRateLimiter(mixed $name, string $expected): void
    {
        $reflectedLimitersProperty = new ReflectionProperty(RateLimiter::class, 'limiters');
        $reflectedLimitersProperty->setAccessible(true);

        $rateLimiter = new RateLimiter($this->createMock(Cache::class));
        $rateLimiter->for($name, fn () => Limit::perMinute(100));

        $limiters = $reflectedLimitersProperty->getValue($rateLimiter);

        $this->assertArrayHasKey($expected, $limiters);

        $limiterClosure = $rateLimiter->limiter($name);

        $this->assertNotNull($limiterClosure);
    }
}

enum BackedEnumNamedRateLimiter: string
{
    case API = 'api';
}

enum UnitEnumNamedRateLimiter
{
    case THIRD_PARTY;
}
